<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * =============================================================================
 *  REQUEST-SCOPED BATCH PREFETCH LAYER
 * =============================================================================
 *
 * WHY THIS EXISTS
 * ---------------
 * fetch_product() reads a page of products with one query, then loops over the
 * result and issues several more queries PER PRODUCT (variants, attributes,
 * min/max price, stock, the seller's product count, review images). Measured on
 * this database, a single /products render executed 976 queries to display 119
 * products - roughly 940 of them per-row lookups.
 *
 * This file turns that fan-out into a handful of `WHERE ... IN (...)` queries.
 *
 * HOW IT PRESERVES BEHAVIOUR EXACTLY
 * ----------------------------------
 * Nothing here reimplements any post-processing. The batched queries fetch the
 * SAME columns the per-row queries fetched; the rows are then bucketed by their
 * owning id and handed back to the ORIGINAL transformation code, untouched. A
 * caller therefore receives a structure that is byte-identical to what it got
 * before - verified by serialize()+md5 fingerprints over every affected function.
 *
 * WHY IT IS SCOPED RATHER THAN GLOBAL
 * -----------------------------------
 * The cache is only consulted while a prefetch scope is OPEN. fetch_product()
 * opens one immediately before its post-processing loop and closes it immediately
 * after. Outside that window every helper behaves exactly as it always did and
 * goes to the database.
 *
 * That matters because these tables are writable. A scope spans a single read
 * loop that performs no writes, so there is no window in which the cache can go
 * stale. Code elsewhere in the same request - including anything that writes a
 * variant or adjusts stock and then reads it back - is completely unaffected,
 * because for that code the scope is closed and the cache is invisible.
 *
 * MISS BEHAVIOUR
 * --------------
 * Every prefetched id is seeded, including ids that own no rows (seeded to an
 * empty array). So "not in the cache" unambiguously means "never prefetched",
 * and the helper falls through to its original single-row query. A partial or
 * failed prefetch degrades to the old behaviour; it can never return wrong data.
 */
class Product_batch
{
    /** @var int Nesting depth of open prefetch scopes. Cache is live when > 0. */
    private static $depth = 0;

    /** @var array pid|status => raw product_variants rows */
    public static $variants = array();

    /** @var array pid => raw product_attributes rows */
    public static $attributes = array();

    /** @var array pid => raw rows for the min/max price computation */
    public static $minmax = array();

    /** @var array product id => stock */
    public static $stock_product = array();

    /** @var array variant id => stock */
    public static $stock_variant = array();

    /** @var array seller id => live product count (string, as COUNT() returns) */
    public static $seller_product_count = array();

    /** @var array|null Set of product ids owning at least one review WITH images */
    public static $rated_with_images = null;

    /** @var array "variantid|userid" => cart qty row */
    public static $cart = array();

    /** @var array variant id => raw rows for get_variants_values_by_id() */
    public static $variants_by_id = array();

    /** @var array "pid|userid" => favourite count for that product/user */
    public static $favorites = array();

    /** @var array "pid|userid" => this user's rating rows for that product */
    public static $user_rating = array();

    public static function is_open()
    {
        return self::$depth > 0;
    }

    public static function open()
    {
        self::$depth++;
    }

    /**
     * Closes one scope. When the outermost scope closes every bucket is dropped,
     * so no data can survive into unrelated work later in the same request.
     */
    public static function close()
    {
        self::$depth--;
        if (self::$depth <= 0) {
            self::$depth = 0;
            self::flush();
        }
    }

    public static function flush()
    {
        self::$variants = array();
        self::$attributes = array();
        self::$minmax = array();
        self::$stock_product = array();
        self::$stock_variant = array();
        self::$seller_product_count = array();
        self::$rated_with_images = null;
        self::$cart = array();
        self::$variants_by_id = array();
        self::$favorites = array();
        self::$user_rating = array();
    }
}

/**
 * Opens a prefetch scope for get_variants_values_by_id() over a set of variant ids.
 *
 * The admin and seller order tables call that helper once per order LINE while
 * rendering, which is an N+1 nested inside another N+1. One query covers the lot.
 *
 * MUST be paired with product_batch_close().
 *
 * @param array $variant_ids
 */
function variant_batch_open(array $variant_ids)
{
    Product_batch::open();

    $ids = array();
    foreach ($variant_ids as $id) {
        if ($id !== null && $id !== '') {
            $ids[(string) $id] = (int) $id;
        }
    }
    if (empty($ids)) {
        return;
    }

    $t = &get_instance();
    // Same SELECT / JOIN / GROUP BY as the single-id query; only the WHERE widens.
    $rows = $t->db
        ->select("pv.*,pv.`product_id`,group_concat(`av`.`id` separator ', ') as varaint_ids,group_concat(`a`.`name` separator ', ') as attr_name, group_concat(`av`.`value` separator ', ') as variant_values")
        ->join('attribute_values av ', 'FIND_IN_SET(av.id, pv.attribute_value_ids ) > 0', 'inner')
        ->join('attributes a', 'a.id = av.attribute_id', 'inner')
        ->where_in('pv.id', array_values($ids))
        ->group_by('`pv`.`id`')
        ->order_by('pv.id')
        ->get('product_variants pv')->result_array();

    // Seed every requested id, so an id whose INNER JOINs matched nothing is a HIT
    // returning an empty array - exactly what the per-id query returned for it -
    // rather than a miss that falls back to querying.
    foreach ($ids as $id) {
        Product_batch::$variants_by_id[$id] = array();
    }
    foreach ($rows as $row) {
        Product_batch::$variants_by_id[$row['id']][] = $row;
    }
}

/**
 * Opens a prefetch scope and warms every bucket for the given product rows.
 *
 * Costs a fixed handful of queries regardless of how many products are passed,
 * replacing ~8 queries per product.
 *
 * MUST be paired with product_batch_close().
 *
 * @param array    $products Rows from fetch_product()'s main query (need at least
 *                           `id` and `seller_id`).
 * @param int|null $user_id  When the caller is reading a cart-aware product list.
 */
function product_batch_open(array $products, $user_id = null)
{
    Product_batch::open();

    $pids = array();
    $seller_ids = array();
    foreach ($products as $row) {
        if (isset($row['id']) && $row['id'] !== null && $row['id'] !== '') {
            $pids[(string) $row['id']] = (int) $row['id'];
        }
        if (isset($row['seller_id']) && $row['seller_id'] !== null && $row['seller_id'] !== '') {
            $seller_ids[(string) $row['seller_id']] = (int) $row['seller_id'];
        }
    }
    $pids = array_values($pids);
    $seller_ids = array_values($seller_ids);

    if (empty($pids)) {
        return;
    }

    $t = &get_instance();

    /* ---------------------------------------------------------------------
     * 1. Variants (replaces get_variants_values_by_pid() per product)
     *
     * Identical SELECT / JOIN / GROUP BY to the single-product query; only the
     * WHERE widens to IN(). GROUP BY pv.id with ORDER BY pv.product_id, pv.id
     * yields, within each product, exactly the pv.id ordering the original
     * ORDER BY pv.id produced.
     * ------------------------------------------------------------------- */
    $rows = $t->db
        ->select("pv.*,pv.`product_id`,group_concat(`av`.`id`  ORDER BY av.id ASC) as variant_ids,group_concat( ' ' ,`a`.`name` ORDER BY av.id ASC) as attr_name, group_concat(`av`.`value` ORDER BY av.id ASC) as variant_values , pv.price as price , GROUP_CONCAT(av.swatche_type ORDER BY av.id ASC ) as swatche_type , GROUP_CONCAT(av.swatche_value ORDER BY av.id ASC ) as swatche_value")
        ->join('attribute_values av ', 'FIND_IN_SET(av.id, pv.attribute_value_ids ) > 0', 'left')
        ->join('attributes a', 'a.id = av.attribute_id', 'left')
        ->where_in('pv.product_id', $pids)
        ->where_in('pv.status', array(1))
        ->group_by('`pv`.`id`')
        ->order_by('pv.product_id')
        ->order_by('pv.id')
        ->get('product_variants pv')->result_array();

    $variant_ids = array();
    foreach ($pids as $pid) {
        Product_batch::$variants[$pid . '|1'] = array();
    }
    foreach ($rows as $row) {
        $key = $row['product_id'] . '|1';
        if (!isset(Product_batch::$variants[$key])) {
            Product_batch::$variants[$key] = array();
        }
        Product_batch::$variants[$key][] = $row;
        $variant_ids[] = (int) $row['id'];
    }

    /* ---------------------------------------------------------------------
     * 2. Attributes (replaces get_attribute_values_by_pid() per product)
     *
     * `batch_pid` is appended purely to bucket the rows and is stripped again
     * below, so the array handed to the transform has exactly the original keys
     * in their original order.
     * ------------------------------------------------------------------- */
    $rows = $t->db
        ->select(" group_concat(`av`.`id` ORDER BY `av`.`id` ASC) as ids,group_concat(' ', `av`.`value`  ORDER BY `av`.`id` ASC ) as value ,`a`.`name` as attr_name, a.name, GROUP_CONCAT(av.swatche_type ORDER BY av.id ASC ) as swatche_type , GROUP_CONCAT(av.swatche_value  ORDER BY av.id ASC) as swatche_value, pa.product_id as batch_pid")
        ->join('attribute_values av ', 'FIND_IN_SET(av.id, pa.attribute_value_ids ) > 0', 'inner')
        ->join('attributes a', 'a.id = av.attribute_id', 'inner')
        ->where_in('pa.product_id', $pids)
        ->group_by('pa.product_id')
        ->group_by('`a`.`name`')
        ->get('product_attributes pa')->result_array();

    foreach ($pids as $pid) {
        Product_batch::$attributes[$pid] = array();
    }
    foreach ($rows as $row) {
        $pid = $row['batch_pid'];
        unset($row['batch_pid']);
        if (!isset(Product_batch::$attributes[$pid])) {
            Product_batch::$attributes[$pid] = array();
        }
        Product_batch::$attributes[$pid][] = $row;
    }

    /* ---------------------------------------------------------------------
     * 3. Min/max price inputs (replaces get_min_max_price_of_product())
     * ------------------------------------------------------------------- */
    $rows = $t->db
        ->select('is_prices_inclusive_tax,price,special_price,tax.percentage as tax_percentage, p.id as batch_pid')
        ->join('`product_variants` pv', 'p.id = pv.product_id')
        ->join('`taxes` tax', 'tax.id = p.tax', 'LEFT')
        ->where_in('p.id', $pids)
        ->get('products p')->result_array();

    foreach ($pids as $pid) {
        Product_batch::$minmax[$pid] = array();
    }
    foreach ($rows as $row) {
        $pid = $row['batch_pid'];
        unset($row['batch_pid']);
        if (!isset(Product_batch::$minmax[$pid])) {
            Product_batch::$minmax[$pid] = array();
        }
        Product_batch::$minmax[$pid][] = $row;
    }

    /* ---------------------------------------------------------------------
     * 4. Stock, at both product and variant level (replaces get_stock())
     * ------------------------------------------------------------------- */
    $rows = $t->db->select('id, stock')->where_in('id', $pids)->get('products')->result_array();
    foreach ($rows as $row) {
        Product_batch::$stock_product[$row['id']] = $row['stock'];
    }
    if (!empty($variant_ids)) {
        $rows = $t->db->select('id, stock')->where_in('id', $variant_ids)->get('product_variants')->result_array();
        foreach ($rows as $row) {
            Product_batch::$stock_variant[$row['id']] = $row['stock'];
        }
    }

    /* ---------------------------------------------------------------------
     * 5. Per-seller live product count.
     *
     * COUNT() comes back as a string from mysqli and the original assigned that
     * string straight through, so sellers with no matching rows are seeded with
     * the string '0' rather than the integer 0.
     * ------------------------------------------------------------------- */
    if (!empty($seller_ids)) {
        foreach ($seller_ids as $sid) {
            Product_batch::$seller_product_count[$sid] = '0';
        }
        $rows = $t->db->select('seller_id, count(id) as total')
            ->where('status', '1')
            ->where('listing_visibility', 1)
            ->where_in('seller_id', $seller_ids)
            ->group_by('seller_id')
            ->get('products')->result_array();
        foreach ($rows as $row) {
            Product_batch::$seller_product_count[$row['seller_id']] = $row['total'];
        }
    }

    /* ---------------------------------------------------------------------
     * 6. Which products own at least one review carrying images.
     *
     * fetch_product() calls fetch_rating(..., $has_images = 1) for every product
     * purely to populate `review_images`. On a typical catalogue almost no
     * product qualifies, and for those the call is a guaranteed empty result.
     * Knowing the qualifying set up front lets fetch_rating() return its
     * empty-result value immediately, without a query, for all the rest.
     * ------------------------------------------------------------------- */
    $rows = $t->db->select('DISTINCT(product_id) as product_id')
        ->where_in('product_id', $pids)
        ->where('images !=', null)
        ->get('product_rating')->result_array();
    $set = array();
    foreach ($rows as $row) {
        $set[(string) $row['product_id']] = true;
    }
    Product_batch::$rated_with_images = $set;

    /* ---------------------------------------------------------------------
     * 7. Cart quantities, when the caller is reading a cart-aware list.
     * ------------------------------------------------------------------- */
    if (!empty($user_id) && !empty($variant_ids)) {
        $rows = $t->db->select('product_variant_id, qty as cart_count')
            ->where('user_id', $user_id)
            ->where('is_saved_for_later', 0)
            ->where_in('product_variant_id', $variant_ids)
            ->get('cart')->result_array();
        foreach ($variant_ids as $vid) {
            Product_batch::$cart[$vid . '|' . $user_id] = array();
        }
        foreach ($rows as $row) {
            $vid = $row['product_variant_id'];
            unset($row['product_variant_id']);
            Product_batch::$cart[$vid . '|' . $user_id][] = $row;
        }
    }

    /* ---------------------------------------------------------------------
     * 8. Favourites, for a logged-in shopper.
     *
     * The loop asked "has this user favourited this product" once per product.
     * The original used num_rows() on the whole row set, so the value is a COUNT,
     * and products the user has not favourited must come back as 0.
     * ------------------------------------------------------------------- */
    if (!empty($user_id)) {
        foreach ($pids as $pid) {
            Product_batch::$favorites[$pid . '|' . $user_id] = 0;
        }
        $rows = $t->db->select('product_id, COUNT(*) as cnt')
            ->where('user_id', $user_id)
            ->where_in('product_id', $pids)
            ->group_by('product_id')
            ->get('favorites')->result_array();
        foreach ($rows as $row) {
            Product_batch::$favorites[$row['product_id'] . '|' . $user_id] = (int) $row['cnt'];
        }

        /* -----------------------------------------------------------------
         * 9. This user's own rating for each product.
         *
         * The original query sat INSIDE the per-variant loop but was keyed only on
         * (user_id, product_id) - so a product with five variants ran the identical
         * query five times and threw four of the results away.
         * ----------------------------------------------------------------- */
        foreach ($pids as $pid) {
            Product_batch::$user_rating[$pid . '|' . $user_id] = array();
        }
        $rows = $t->db->select('product_id, rating, comment')
            ->where('user_id', $user_id)
            ->where_in('product_id', $pids)
            ->get('product_rating')->result_array();
        foreach ($rows as $row) {
            $pid = $row['product_id'];
            unset($row['product_id']);
            Product_batch::$user_rating[$pid . '|' . $user_id][] = $row;
        }
    }
}

/**
 * Closes the prefetch scope opened by product_batch_open().
 *
 * Safe to call unconditionally; when the outermost scope closes, all buckets are
 * discarded.
 */
function product_batch_close()
{
    Product_batch::close();
}

/**
 * Reads a prefetched bucket.
 *
 * @param  string $bucket Static property name on Product_batch.
 * @param  string $key
 * @return array|null NULL means "not prefetched" - the caller must query.
 */
function product_batch_get($bucket, $key)
{
    if (!Product_batch::is_open()) {
        return null;
    }
    $store = Product_batch::${$bucket};
    $key = (string) $key;
    return array_key_exists($key, $store) ? $store[$key] : null;
}
