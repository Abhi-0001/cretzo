# Phase 2 — Settings Caching & N+1 Query Elimination

**Date:** 2026-08-25
**Scope:** Steps 1 and 2 of the agreed optimization plan (settings memoization, hook
caching, `fetch_product()` N+1 batching, `Order_model` N+1 batching).
**Guarantee:** no business logic, UI element, or API response shape was changed.

---

## 1. Results

### Page-level (measured against local XAMPP, `cretzo_db`, 291 products)

| Page | Queries before | Queries after | TTFB before | TTFB after | Speed-up |
|---|---:|---:|---:|---:|---:|
| `/products` | 976 | **149** | 2.83 s | **0.64 s** | **4.4×** |
| `/` (home) | 557 | **273** | 1.55 s | **0.88 s** | **1.8×** |
| `/sellers` | 118 | **105** | 0.54 s | **0.39 s** | 1.4× |
| `/cart` | — | 20 | 0.28 s | 0.33 s | — |
| `/login` | — | 17 | 0.21 s | 0.24 s | — |

### Function-level (CLI harness exercising every affected read path)

| Harness run | Queries before | Queries after | Reduction |
|---|---:|---:|---:|
| `perfcheck snapshot` (24 `fetch_product` shapes + all helpers + all settings) | 1489 | **211** | **86%** |
| `perfcheck orders` (admin Orders + Digital Orders tables) | 79 | **11** | **86%** |

> The per-product costs removed scale **linearly with catalogue size**. At 3,000
> products the old `/products` path would have issued roughly 24,000 queries;
> the new one still issues a fixed handful plus the page query.

---

## 2. Correctness verification

Three independent proofs were run. All passed.

### 2.1 Output fingerprints — 123/123 identical

A temporary CLI harness (`application/controllers/Perfcheck.php`) captures
`md5(serialize($result))` for every affected function across 123 cases:

- 24 `fetch_product()` shapes — default storefront, page 2, wide page, price sort,
  `new_added_products`, `top_rated_products`, `products_on_sale`,
  `most_selling_products`, search, admin-mode (`show_only_active_products = 0`),
  `return_count`, six by-product-id, four by-seller-id.
- `get_variants_values_by_pid`, `get_attribute_values_by_pid`,
  `get_min_max_price_of_product`, `get_stock` (product **and** variant),
  `fetch_rating` — six products each.
- `get_settings()` and `get_settings($v, true)` for **every one of the 32 variables**
  in the `settings` table.

Every fingerprint is byte-identical before and after.

### 2.2 Rendered HTML — 13/13 identical

With a temporary kill switch toggling the batch layer on and off, the full HTML of
13 real URLs was captured both ways and compared byte-for-byte:

```
/                                              IDENTICAL (258,951 B)
/products                                      IDENTICAL (161,449 B)
/sellers                                       IDENTICAL (141,193 B)
/cart                                          IDENTICAL  (79,598 B)
/products/details/colorful-cottage-knit-...    IDENTICAL (196,151 B)
/products/category/jewellery-and-accessories-1 IDENTICAL (147,816 B)
/products/category/footwear-and-bags           IDENTICAL (146,819 B)
/sellers/clayya                                IDENTICAL  (25,475 B)
/products?sort=pv.price&order=asc              IDENTICAL (161,569 B)
/products?min_price=100&max_price=5000         IDENTICAL (161,594 B)
   (+ 3 redirect responses, identical on both sides)
```

The **only** byte that ever differed was the `csrf-token-hash`, which CodeIgniter
regenerates per request by design. The kill switch was removed afterwards; it is
not in the shipped code.

### 2.3 Admin order tables — JSON identical

`get_orders_list()` and `get_digital_product_orders_list()` print JSON directly.
The full 51 KB payload is byte-identical before and after; only the query count
changed (79 → 11).

---

## 3. Changes, file by file

### 3.1 `application/helpers/function_helper.php` — settings memoization

`get_settings()` previously issued a fresh `SELECT *` on **every** call. There are
~700 call sites; `/products` alone made 25 of them for a handful of variables, and
the policy rows in that table are large (`seller_terms_conditions` is 13 KB).

Four functions were added:

| Function | Purpose |
|---|---|
| `settings_cache_store()` | Returns the memo array **by reference**. |
| `get_settings_raw($type)` | Raw `value` string, memoised. `FALSE` = no such row. |
| `clear_settings_cache($type = null)` | Drops one variable, or all of them. |
| `settings_write_done($result)` | Pass-through that invalidates, then returns `$result` unchanged. |

`get_settings()` now reads through `get_settings_raw()`. The return contract is
preserved exactly, including edge cases:

- no such row → `NULL` (implicit, as before)
- `$is_json = true` → `json_decode($value, true)`
- `$is_json = false` → `output_escaping($value)`

The `FALSE`-means-absent sentinel matters: a row whose value is the string `'0'`
must `json_decode()` to integer `0`, whereas a missing row must yield `NULL`. A
naive `empty()` check would have conflated the two.

The `SELECT *` was also narrowed to `SELECT value` — the only column ever read.

**Result on `/products`: 25 settings queries → 9** (one per distinct variable).

### 3.2 Cache invalidation — 64 write sites

Because settings are writable, every write must drop the memo or a save-then-read
inside one request would see stale data. All 64 write sites were wrapped:

| File | Sites | Method |
|---|---:|---|
| `application/models/Setting_model.php` | 58 | `settings_write_done(...)` wrapper |
| `application/libraries/Shiprocket.php` | 2 | `settings_write_done(...)` wrapper |
| `application/migrations/*.php` | 4 | `settings_write_done(...)` wrapper |
| `application/migrations/048`, `062` | 2 | explicit `clear_settings_cache()` (multi-line statements) |

The wrapper is a pass-through, so sites in `return` position keep returning exactly
what they returned before:

```php
return settings_write_done($this->db->set('value', $d)->where('variable', 'x')->update('settings'));
```

### 3.3 `application/hooks/MyConfig.php` — hook caching

The 84-entry `base_url()` exclusion list was written out inline **twice**. A script
verified both copies were entry-for-entry and order-for-order identical before
consolidating them into `MyConfig::exclude_uris()`, built once per request, plus
`MyConfig::is_excluded_uri()` which resolves `current_url()` once instead of six
times.

`get_email_settings()` ran its own `SELECT * FROM settings` on every request whether
or not the request sends mail; it now reads through `get_settings_raw()` and shares
the memo. It still decodes to an **object** (not an assoc array), which is what the
`->` accesses below it require.

> **Correction to the Phase 1 report.** Phase 1 stated ~180 wasted `base_url()`
> calls per request. The real figure is **84**. `loadSystemResources()`'s copy of the
> list sits inside `if (!method_exists('MyConfig', 'verify_doctor_brown'))`, which is
> always `TRUE` — that branch never executed, so only `verify_doctor_brown()`'s copy
> was ever built. Phase 1 also stated 121 settings queries on `/products`; the true
> figure was **25**. That number came from a shell-mangled `grep` and is corrected here.

### 3.4 `application/helpers/batch_helper.php` — new file

The batch prefetch layer. Autoloaded via `application/config/autoload.php`.

```
$autoload['helper'] = array('url','form','security','function_helper','batch');
```

**`Product_batch`** holds eight buckets plus a scope depth counter:

| Bucket | Replaces |
|---|---|
| `$variants` | `get_variants_values_by_pid()` per product |
| `$attributes` | `get_attribute_values_by_pid()` per product |
| `$minmax` | `get_min_max_price_of_product()` per product |
| `$stock_product` / `$stock_variant` | `get_stock()` per product / per variant |
| `$seller_product_count` | the inline seller `COUNT(id)` per product |
| `$rated_with_images` | `fetch_rating(..., $has_images = 1)` per product |
| `$cart` | the cart lookup per variant, for logged-in shoppers |
| `$variants_by_id` | `get_variants_values_by_id()` per order line |

**`product_batch_open($products, $user_id)`** warms every bucket in 7 queries.
**`variant_batch_open($variant_ids)`** warms `$variants_by_id` in 1.
**`product_batch_close()`** closes the scope and flushes.

#### The three safety properties

1. **No post-processing was reimplemented.** The batched queries select the *same
   columns* as the per-row queries; rows are bucketed by owning id and handed to
   the **original, untouched** transformation code. The batch is a data-access
   change only.

2. **The cache is scoped, not global.** It is consulted only while a scope is open.
   `fetch_product()` opens one immediately before its post-processing loop and
   closes it immediately after, in a `finally` block. Outside that window every
   helper behaves exactly as it always did and goes to the database. Since a scope
   spans a pure read loop that performs no writes, there is no window in which the
   cache can go stale — and code elsewhere in the request that writes a variant or
   adjusts stock and reads it back is entirely unaffected, because for that code the
   scope is closed and the cache is invisible.

3. **A miss can never return wrong data.** Every requested id is *seeded*, including
   ids that own no rows (seeded to an empty array). So "absent from the bucket"
   unambiguously means "never prefetched", and the helper falls through to its
   original single-row query. A partial or failed prefetch silently degrades to the
   old behaviour.

#### Ordering equivalence

Each batched query reproduces the original's row order within each bucket:

- **variants** — `GROUP BY pv.id ORDER BY pv.product_id, pv.id`; within a product
  this is exactly the original `ORDER BY pv.id`.
- **attributes** — `GROUP BY pa.product_id, a.name`; within a product this is the
  original `GROUP BY a.name` ordering (MariaDB sorts by the `GROUP BY` columns).
- **variants_by_id** — one row per id, so order is immaterial.
- **order items** — same `order_id` index drives both, so per-order order is unchanged.

The `batch_pid` bucketing column is `unset()` before the rows reach the transform,
so the arrays carry exactly the original keys **in their original order** —
necessary because `output_escaping()` rebuilds arrays in iteration order and the
fingerprints compare `serialize()` output.

#### Type fidelity

`COUNT()` returns a **string** from mysqli and the original assigned that string
straight through. Sellers with no matching products are therefore seeded with the
string `'0'`, not integer `0`.

### 3.5 `fetch_product()` — the main win

- Scope opened before the post-processing loop, closed in `finally`.
- **Removed a duplicated call.** `get_variants_values_by_pid()` was invoked *twice*
  with identical arguments and the two identical results assigned to two different
  variables — one wasted query per product, 119 per listing page. The one result now
  feeds both.
- The seller `COUNT` and the per-variant cart lookup read from the batch.

### 3.6 `Rating_model::fetch_rating()` — targeted early-out

`fetch_product()` calls this once per product with `$has_images = 1` purely to
populate `review_images`. Hardly any product owns a review carrying images, so for
the vast majority the query is guaranteed to return nothing.

One `DISTINCT` query in the prefetch establishes exactly which listed products
qualify. For any product outside that set the function returns its empty-result
value (`NULL`) immediately.

The guard is deliberately narrow — it applies **only** to the exact call shape
`fetch_product()` uses (product-scoped, images-only, no user or rating filter).
Every other caller still runs the query.

### 3.7 `Order_model` — two N+1 loops

`get_orders_list()` and `get_digital_product_orders_list()` each ran the order-items
query once **per order**, then called `get_variants_values_by_id()` once per order
**line** — an N+1 nested inside an N+1.

Both now issue one `WHERE oi.order_id IN (...)` for the whole page and bucket the
rows back onto their orders, then open a `variant_batch_open()` scope (closed in
`finally`) around the rendering loop. The digital variant keeps its
`p.type = 'digital_product'` restriction unchanged.

**79 → 11 queries**, output byte-identical.

---

## 4. Pre-existing bug fixed (required to run the tests)

`application/helpers/function_helper.php`, in the `fetch_product()` loop:

```php
if (!empty($product[$i]['variants'])) {
    $count_stock = array();          // ← declared INSIDE the guard
    $is_purchased_count = array();
    ...
}
$is_purchased_count = array_count_values($is_purchased_count);   // ← used OUTSIDE it
```

A product with **no variant rows** reached
`array_count_values(): Argument #1 ($value) must be of type array, null given` — a
**fatal `TypeError` on PHP 8**.

The storefront never hit it because its `WHERE` carries `pv.status = 1`, which
filters variantless products out. But every administrative read passing
`show_only_active_products = 0` (Manage Stock, product exports) does reach them, and
**13 active products on this database have no variant rows**.

Both accumulators are now declared before the guard. Semantics are preserved
exactly: for a variantless product both stay empty, `array_sum([])` is `0`, and
`is_purchased` resolves to `false` — the same value the loop would have produced had
it run and pushed nothing.

---

## 5. Files changed

```
 application/config/autoload.php                       |   2 +-
 application/helpers/batch_helper.php                  | NEW (+340)
 application/helpers/function_helper.php               | +150 / -30
 application/hooks/MyConfig.php                        | -51
 application/libraries/Shiprocket.php                  |   2 +-
 application/migrations/015,048,052,054,062            |   6 +-
 application/models/Order_model.php                    | +79 / -20
 application/models/Rating_model.php                   |  +28
 application/models/Setting_model.php                  | 116 +-
 application/controllers/Perfcheck.php                 | NEW — TEMPORARY, see below
```

`index.php` and `application/config/database.php` also show as modified — those are
**pre-existing local edits, not part of this work**.

---

## 6. Outstanding items

1. **`application/controllers/Perfcheck.php` must be deleted before deploy.** It is
   the verification harness. It is CLI-only (`show_404()` on any web request), but it
   has no business shipping. It is retained for now so Phases 3 and 4 can be verified
   against the same fingerprints.

2. **Not yet done** (agreed for later steps): Item 6 external API timeouts, Item 7
   environment and logging, Item 5 missing indexes, Item 3 OPcache, Item 4 gzip and
   deferred assets.

3. **`/` (home) still issues 273 queries.** The `fetch_product` path there is now
   optimal; the remainder comes from the homepage's category/section/brand tree
   walks, which are a separate N+1 not in the agreed scope for this step. Worth
   raising as a candidate for a follow-up.

---

## 7. How to re-verify

```bash
# From the project root, with the local DB running:
php index.php perfcheck snapshot > after.txt
php index.php perfcheck orders   > after_orders.txt

# Compare against the stored baselines (fingerprints only; ignore the query count line):
diff <(grep -v QUERIES before.txt) <(grep -v QUERIES after.txt)
```

A clean diff means every affected read path returns byte-identical data.
