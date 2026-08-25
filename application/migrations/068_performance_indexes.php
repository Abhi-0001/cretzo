<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Indexes for the storefront read path.
 *
 * EXPLAIN on the main fetch_product() query showed three tables being FULL SCANNED
 * and joined with Block Nested Loop, once per driving row:
 *
 *     p    type=ref  key=idx_products_status_visibility  rows=114
 *     b    type=ALL  key=NULL   <- brands, joined on p.brand = b.name
 *     ss   type=ALL  key=NULL   <- seller_subscriptions, joined on seller_id
 *     sd   type=ALL  key=NULL   <- seller_data, joined on p.seller_id = sd.user_id
 *
 * seller_data in particular carried NOTHING but a PRIMARY key, while every single
 * storefront read joins it on user_id. With 13 sellers the scan is cheap; the cost
 * is multiplicative, so it grows with sellers x subscriptions x products.
 *
 * Everything here is additive. No column, constraint or row is altered, so the
 * behaviour of every query is identical - only the access path changes.
 *
 * PREFIX LENGTHS
 * --------------
 * The varchar indexes use a 191-character prefix. These tables are utf8mb4, so 191
 * chars is 764 bytes and fits the 767-byte limit that older MySQL builds impose on
 * COMPACT/REDUNDANT rows. products.slug and seller_data.slug are varchar(512),
 * which at 4 bytes/char would be 2048 bytes and would fail outright on such a
 * server. Every one of these columns is used for exact-match lookup, where a
 * 191-char prefix is effectively fully selective.
 *
 * IDEMPOTENT
 * ----------
 * Each index is created only if the table exists and an index of that name does
 * not. The migration can be re-run safely, and it will not collide with an index
 * an administrator may have added by hand.
 */
class Migration_performance_indexes extends CI_Migration
{
    /**
     * name => [table, column list]
     *
     * Raw DDL is built from these rather than using $this->dbforge, because dbforge
     * cannot express prefix lengths and several of these require them.
     */
    private function index_definitions()
    {
        return [
            /*
             * seller_data: joined by EVERY storefront read as
             *   LEFT JOIN seller_data sd ON p.seller_id = sd.user_id
             * with `sd.status = 1` in the WHERE. The composite serves the join and
             * the filter together, and its user_id prefix serves lookups that only
             * need the join.
             */
            'idx_seller_data_user_status' => ['seller_data', '(`user_id`, `status`)'],
            // Seller storefront pages resolve by slug.
            'idx_seller_data_slug'        => ['seller_data', '(`slug`(191))'],

            /*
             * brands: joined on p.brand = b.name - a string join with no index at
             * all, hence the full scan.
             */
            'idx_brands_name'             => ['brands', '(`name`(191))'],
            'idx_brands_slug'             => ['brands', '(`slug`(191))'],

            /*
             * seller_subscriptions: joined as
             *   LEFT JOIN seller_subscriptions ss
             *     ON ss.seller_id = p.seller_id AND ss.is_active = 1
             * and its result drives ORDER BY has_subscription DESC, so it is touched
             * for every product row.
             */
            'idx_seller_subs_seller_active' => ['seller_subscriptions', '(`seller_id`, `is_active`)'],

            /*
             * products: seller_id had no index despite driving the seller storefront
             * filter AND the per-seller live product count (which the Phase 2 batch
             * now runs as a single GROUP BY over seller_id, exactly matching this
             * index's leading column).
             */
            'idx_products_seller_status'  => ['products', '(`seller_id`, `status`, `listing_visibility`)'],
            // Product detail pages resolve by slug.
            'idx_products_slug'           => ['products', '(`slug`(191))'],
            // Brand filter, and the b.name join's other side.
            'idx_products_brand'          => ['products', '(`brand`(191))'],

            /*
             * product_variants: the existing index is on product_id alone, but every
             * storefront read also filters pv.status. Adding status lets the filter be
             * satisfied from the index instead of by reading each row.
             */
            'idx_variants_product_status' => ['product_variants', '(`product_id`, `status`)'],

            /*
             * categories: c.status is filtered on every storefront read (the
             * '1' OR '0' OR NULL group), and category pages resolve by slug.
             */
            'idx_categories_status'       => ['categories', '(`status`)'],
            'idx_categories_slug'         => ['categories', '(`slug`(191))'],

            /*
             * product_rating: product_id is already indexed, but the reviews-with-images
             * lookup that the Phase 2 batch performs filters on images being non-NULL as
             * well. A (product_id, images(1)) prefix is enough to answer "does this
             * product have any review carrying images" from the index alone.
             */
            'idx_rating_product_images'   => ['product_rating', '(`product_id`, `images`(1))'],
        ];
    }

    public function up()
    {
        /*
         * CodeIgniter's db_debug does not throw - it calls show_error() and halts the
         * request outright. Suppressing it for the duration of this migration is what
         * makes the per-index error handling below actually reachable, so one index
         * that cannot be built does not take down the whole migration run. It is
         * restored in the finally block.
         */
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;

        try {
            $this->create_indexes();
        } finally {
            $this->db->db_debug = $db_debug;
        }
    }

    private function create_indexes()
    {
        foreach ($this->index_definitions() as $name => $definition) {
            list($table, $columns) = $definition;

            if (!$this->db->table_exists($table)) {
                log_message('error', "068_performance_indexes: table `$table` missing, skipped index `$name`");
                continue;
            }
            if ($this->index_exists($table, $name)) {
                log_message('debug', "068_performance_indexes: `$name` already present, skipped");
                continue;
            }

            /*
             * A failure here must not abort the migration run. An index is a pure
             * optimisation - if one cannot be created (an unexpected column type on a
             * differently-migrated database, or a prefix limit on an exotic row
             * format), the application still works exactly as it did, just without
             * that index. Log it and carry on rather than leaving the schema
             * half-migrated and the migration version stuck.
             */
            if ($this->db->query("ALTER TABLE `$table` ADD INDEX `$name` $columns") === false) {
                $error = $this->db->error();
                log_message('error', "068_performance_indexes: could not create `$name` on `$table`: "
                    . (isset($error['message']) ? $error['message'] : 'unknown error'));
            } else {
                log_message('debug', "068_performance_indexes: created `$name` on `$table`");
            }
        }
    }

    public function down()
    {
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            foreach ($this->index_definitions() as $name => $definition) {
                list($table,) = $definition;
                if (!$this->db->table_exists($table) || !$this->index_exists($table, $name)) {
                    continue;
                }
                if ($this->db->query("DROP INDEX `$name` ON `$table`") === false) {
                    $error = $this->db->error();
                    log_message('error', "068_performance_indexes (down): could not drop `$name`: "
                        . (isset($error['message']) ? $error['message'] : 'unknown error'));
                }
            }
        } finally {
            $this->db->db_debug = $db_debug;
        }
    }

    private function index_exists($table, $index)
    {
        $rows = $this->db->query(
            'SELECT 1 FROM information_schema.statistics
              WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $index]
        )->result_array();
        return !empty($rows);
    }
}
