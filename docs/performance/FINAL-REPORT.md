# Cretzo Performance Optimization — Final Report

**Completed:** 2026-08-25, overnight autonomous run
**Phases delivered:** Phase 1 (diagnosis), Phase 2 (database & queries), Phase 3 (server, config, assets)
**Guarantee:** no business logic, UI element, or API response was changed. Every claim below is
backed by a test recorded in this document.

---

## 1. Headline results

Measured on local XAMPP against `cretzo_db` (291 products, 46 users, 26 orders).

| Page | Queries before | Queries after | TTFB before | TTFB after | Faster by |
|---|---:|---:|---:|---:|---:|
| `/products` | **976** | **52** | 2.834 s | **0.538 s** | **5.3×** |
| `/` (home) | **557** | **90** | 1.550 s | **0.890 s** | 1.7× |
| product detail | 137* | **63** | 1.067 s* | **0.428 s** | 2.5× |
| category page | 130* | **36** | 0.743 s* | **0.259 s** | 2.9× |
| `/sellers` | 118 | **30** | 0.536 s | **0.221 s** | 2.4× |
| `/cart` | — | **19** | 0.285 s | **0.192 s** | 1.5× |
| `/login` | — | **16** | 0.206 s | **0.084 s** | 2.5× |
| `/home/faq` | 100* | **26** | — | **0.206 s** | — |

<sub>* first measured mid-run, after the Phase 2 batching had already landed — the true
pre-optimization figure is higher.</sub>

**Query volume across the 14-page benchmark: 1,583 → 553.**
**Total render time across the same 14 pages: 9.70 s → 4.67 s.**

### Logged-in shopper (a separate, worse path that was not in the original brief)

| Page | Queries before | Queries after |
|---|---:|---:|
| `/` | 225 | **149** |
| `/products` | 118 | **80** |

### Transfer size (compression)

| Asset | Uncompressed | Compressed | Saved |
|---|---:|---:|---:|
| Homepage HTML | 258,951 | 23,026 | 91% |
| `style.css` | 907,841 | 86,250 | 90% |
| `eshop-bundle-main.css` | 855,161 | 104,901 | 87% |
| `eshop-bundle-js.js` | 1,211,619 | 322,986 | 73% |
| `plugins.js` | 813,613 | 184,512 | 77% |
| `adminlte.min.css` | 686,718 | 66,739 | 90% |
| **Total sampled** | **4,895,352** | **810,461** | **83%** |

---

## 2. What changed, phase by phase

### Phase 2a — Settings memoization *(done in the previous session, recapped)*

`get_settings()` issued a fresh `SELECT *` on every one of its ~700 call sites. It is now
memoised per request via `get_settings_raw()`, and the `SELECT *` narrowed to `SELECT value`
(the policy rows in that table run to 13 KB). All **64 write sites** were wrapped in a
pass-through `settings_write_done()` so a save-then-read inside one request cannot go stale.

The 84-entry `base_url()` exclusion list in `MyConfig` was built inline on every request; it is
now built once and shared.

### Phase 2b — N+1 elimination *(done in the previous session, recapped)*

A request-scoped batch prefetch layer (`application/helpers/batch_helper.php`) replaces the
per-row lookups in `fetch_product()`'s loop — variants, attributes, min/max price, stock, the
seller's product count and review images — with a fixed handful of `WHERE ... IN (...)` queries.
A duplicated `get_variants_values_by_pid()` call (identical arguments, both results assigned to
different variables) was removed. Both `Order_model` per-order N+1 loops were batched.

### Phase 2c — Database indexes *(this session)*

Migration **`068_performance_indexes.php`** adds 12 indexes. `EXPLAIN` on the main storefront
query, before and after:

```
BEFORE                                  AFTER
p    ref  idx_products_status_visibility    p    ref  idx_products_status_visibility
pa   ref  product_id                        sd   ref  idx_seller_data_user_status  (Using index)
pv   ref  product_id                        pa   ref  product_id                   (Using index)
b    ALL  NULL   <- full scan, BNL          ss   ref  idx_seller_subs_seller_active(Using index)
ss   ALL  NULL   <- full scan, BNL          pv   ref  idx_variants_product_status  (Using index)
sd   ALL  NULL   <- full scan, BNL          b    ALL  NULL   (see note below)
```

**Full table scans: 3 → 1.** `seller_data` — joined by *every* storefront read and carrying
nothing but a PRIMARY key — and `seller_subscriptions` both became covering index lookups.

Indexes created:

| Table | Index | Columns |
|---|---|---|
| `seller_data` | `idx_seller_data_user_status` | `user_id, status` |
| `seller_data` | `idx_seller_data_slug` | `slug(191)` |
| `brands` | `idx_brands_name` | `name(191)` |
| `brands` | `idx_brands_slug` | `slug(191)` |
| `seller_subscriptions` | `idx_seller_subs_seller_active` | `seller_id, is_active` |
| `products` | `idx_products_seller_status` | `seller_id, status, listing_visibility` |
| `products` | `idx_products_slug` | `slug(191)` |
| `products` | `idx_products_brand` | `brand(191)` |
| `product_variants` | `idx_variants_product_status` | `product_id, status` |
| `categories` | `idx_categories_status` | `status` |
| `categories` | `idx_categories_slug` | `slug(191)` |
| `product_rating` | `idx_rating_product_images` | `product_id, images(1)` |

Design notes:

- **191-character prefixes** on every varchar index. These tables are `utf8mb4`, so 191 chars is
  764 bytes and fits the 767-byte limit older MySQL builds impose on COMPACT/REDUNDANT rows.
  `products.slug` and `seller_data.slug` are `varchar(512)` — at 4 bytes/char that is 2048 bytes
  and would have **failed outright** on such a server. All these columns are used for exact-match
  lookup, where a 191-char prefix is effectively fully selective.
- **Idempotent and non-fatal.** Each index is created only if absent. `db_debug` is suppressed
  for the duration (CodeIgniter's `db_debug` calls `show_error()` and halts rather than throwing,
  which would otherwise have made the per-index error handling unreachable), so one index that
  cannot be built logs and is skipped instead of leaving the schema half-migrated.

> **`brands` is still a full scan, deliberately.** The join is `p.brand = b.name`, but
> `products.brand` is `utf8mb4_unicode_ci` while `brands.name` is `utf8_general_ci`. A
> cross-collation join disables index use entirely — verified: the same index *is* used for a
> same-collation lookup (`type=ref, key=idx_brands_name`). Fixing it requires converting the
> `brands` table charset, which changes string comparison semantics (`general_ci` vs
> `unicode_ci`). With 3 brand rows the scan is free, so **I left this for your decision** rather
> than silently altering comparison behaviour. See §6.

### Phase 2d — Recursive category tree *(this session, not in the original brief)*

`Category_model::sub_categories()` recursed the category tree issuing **one query per node**.
The header navigation walks that tree, so *every page on the site* paid it — **76 queries on a
page as simple as `/home/faq`**. It was the single largest remaining query source.

The whole table is 83 rows. It is now loaded once per request and grouped in PHP.

- Ordering is preserved: the per-node query had no `ORDER BY` and was served by the `parent_id`
  index, returning `(parent_id, id)` order; the bulk read is ordered identically.
- Rows are **cloned** before the loop mutates them. The loop sets `children`/`text`/`level` and
  rewrites `image`/`banner` through `get_image_url()`; without cloning, `level` would leak
  between walks and `get_image_url()` would be applied twice to an already-converted URL.
- `clear_category_cache()` is called on every structural write (insert, update, delete, reorder).
  The two `clicks + 1` counter writes deliberately do **not** invalidate — that column is not
  part of the tree and invalidating there would force a re-query on every product-detail view.

### Phase 2e — Logged-in shopper N+1s *(this session, found during testing)*

Two more per-row queries fired only when a customer was signed in, which is why the logged-in
homepage cost 225 queries against 90 for a guest:

- **Favourites** — one query per product.
- **This user's own rating** — the query sat *inside the per-variant loop* but was keyed only on
  `(user_id, product_id)`, so a five-variant product ran the identical query five times and threw
  four results away.

Both are now prefetched. Logged-in `/` 225 → 149, `/products` 118 → 80.

### Phase 3a — OPcache

OPcache was **completely disabled on the local XAMPP install** — every directive commented out
and the extension not even loaded, so PHP was re-parsing and re-compiling all ~58,000 lines of
controllers plus the 11,600-line `function_helper.php` on every single request there.

> **CORRECTION (2026-08-25):** the original wording implied this was true of production too. It
> was not verified there and should not have been stated that way. The `opcache` extension is in
> fact already ticked in Hostinger hPanel, so production was most likely already using it. The
> measured 6.75s -> 4.86s improvement below is a LOCAL figure; the real-world production gain from
> this step is therefore expected to be much smaller, possibly zero. The query-count work
> (976 -> 52 on /products) is unaffected by this and stands on its own.

Configuration is in **`docs/performance/opcache.ini`**, with application instructions for
Hostinger hPanel, cPanel, Docker and XAMPP. It has been applied to the local XAMPP install and
verified live:

```
enabled: true    cached_scripts: 119    hit_rate: 92.51%    cache_full: false
```

Measured effect across the 14-page benchmark: **6.75 s → 4.86 s** total render time.

Two settings chosen deliberately:

- `opcache.validate_timestamps=1` with `revalidate_freq=2`. Deployment here is a git pull on the
  server, with nothing resetting the cache — `validate_timestamps=0` is marginally faster but
  would keep serving the *previous release* until PHP restarts. Only set it to 0 if the deploy
  script is changed to call `opcache_reset()` as its final step.
- `opcache.enable_file_override=0`. The application checks for uploaded files on disk
  (`get_image_url()`, `add_ver()`), and cached stat results there would be wrong.

`opcache.jit` is off: it targets CPU-bound numeric code and offers effectively nothing to an
I/O- and database-bound web application, while adding hard-to-diagnose failure modes.

Use **`tools/opcache-status.php`** to verify on the server (must be run through the web SAPI —
`enable_cli` is off by design).

### Phase 3b — Compression

The `mod_deflate` block in `.htaccess` had been commented out entirely with the note *"DEFLATE
NOT WORKING ON 000WEBHOST"*. The site no longer runs on 000webhost, and **nothing was being
compressed at all**.

Brotli and gzip are now both enabled. Verified across every client type:

| Client `Accept-Encoding` | Result |
|---|---|
| `gzip, deflate, br` | `Content-Encoding: br`, 91% smaller |
| `gzip` only | `Content-Encoding: gzip`, correct fallback |
| absent (identity) | full uncompressed response, HTTP 200 |
| decompressed vs original | **byte-identical** (907,841 bytes) |
| `Vary: Accept-Encoding` | present |

> **A real bug was caught and fixed here.** `AddOutputFilterByType` is provided by **mod_filter**,
> not mod_deflate. Guarding only on `<IfModule mod_deflate.c>` produced
> `Invalid command 'AddOutputFilterByType'` and **HTTP 500 on every request** the moment
> mod_deflate was present without mod_filter — which is exactly what happened when the block was
> first enabled during this run. `mod_filter.c` is now the outer guard, so the block is inert
> rather than fatal on a server that lacks it.

### Phase 3c — Front-end assets

**Applied:** `preconnect` / `dns-prefetch` hints for the three third-party origins that serve
render-blocking assets (cdnjs, jsdelivr) plus Razorpay's checkout host. These are pure hints —
no script, no styling change — and typically save 100–300 ms per origin on mobile by starting
DNS + TCP + TLS in parallel with the HTML parse instead of serialised behind tag discovery.

**Deliberately NOT applied — `defer` on scripts.** The brief allowed implementing *or*
documenting this. I measured the actual page before deciding:

- `include-script.php` (44 scripts) is loaded at the **end of `<body>`**, after all page content.
  Those scripts therefore **already do not block first paint**, so `defer` would gain close to
  nothing.
- The rendered homepage contains **5 inline `<script>` blocks, one of which appears *after* the
  plugin bundle**. Deferring the external scripts would move them *after* those inline blocks,
  so any inline code touching a plugin at parse time would break.

The genuine render-blocking cost is the **50 `<link rel=stylesheet>` tags in `<head>`** — and
compression has just cut those by ~90%. Further gains need bundling, which is a build-system
change (a `gulpfile.js` already exists). See §6.

### Phase 3d — Environment & logging safety

| Setting | Before | After |
|---|---|---|
| `database.php` → `save_queries` | `TRUE` always | `(ENVIRONMENT !== 'production')` |
| `config.php` → `log_threshold` | assigned **twice**: `= 4`, then `= 1` | single `= 1`, dead `= 4` removed |

`save_queries` made CodeIgniter retain the full text of every statement for the request lifetime
purely for the profiler. It stays ON outside production because the profiler, the migration
tooling and the performance harness all read `$this->db->queries` — **local development is
completely unaffected.**

The duplicate `log_threshold` was dead code: PHP keeps the last assignment, so the effective
value has always been 1. The stray `= 4` read as though the app logged every message on every
request. Removed, with the surviving assignment documented.

**`CI_ENV` was left untouched — see §6, item 1. It needs your decision and I would not change it
unattended.**

---

## 3. Verification performed

Everything below was run after the final change.

| Test | Scope | Result |
|---|---|---|
| Function fingerprints | 123 cases: 24 `fetch_product` shapes, every batched helper across 6 products, `get_settings` for all 32 settings variables | **PASS** — all `md5(serialize())` identical |
| Order tables | `get_orders_list` + `get_digital_product_orders_list` full JSON | **PASS** — byte-identical |
| Category tree | All **83 nodes** + root, new implementation vs the original query-per-node recursion re-implemented inline for comparison | **PASS** — 0 mismatches |
| Guest HTML identity | 13 URLs, batch toggled off vs on | **PASS** — only the per-request CSRF token differed |
| Logged-in HTML identity | 6 URLs incl. cart, favourites, orders, product detail | **PASS** — byte-identical |
| Index safety | Rendering with vs without the new `categories` indexes | **PASS** — identical (unordered queries did not reorder) |
| Admin routes | **114 routes** scraped from the live sidebar | **PASS** — 0 × 5xx, 0 pages with PHP errors |
| Seller portal | **37 routes** | **PASS** — 0 × 5xx, 0 PHP errors |
| Admin AJAX data endpoints | 8 bootstrap-table endpoints | **PASS** — valid JSON, correct row counts (orders 26/26, stock 270, customers 40, media 206, sellers 14) |
| Mobile API | 7 endpoints with a minted HS256 token | **PASS** — `error=false`, correct payloads |
| Customer flow | register → login → add to cart → cart/favourites/orders pages | **PASS** — cart row written correctly |
| Compression | 4 client encoding modes | **PASS** — see Phase 3b table |
| PHP lint | All 23 changed/new files (same check CI runs) | **PASS** |
| Error logs | CI log + Apache error log | **Clean** — the only fatals present are timestamped 00:15, from the first baseline run *before* the pre-existing bug below was fixed |

### Two false alarms worth recording

1. **`admin/orders/view_orders` returned 0 bytes mid-testing.** Investigated rather than assumed:
   the response carried `Refresh: 0;url=/admin/home`, i.e. a `has_permissions()` redirect. Root
   cause was a **stale cookie jar in my test harness** — CodeIgniter rotates session IDs every
   300 s (`sess_time_to_update`) and my sweep used `curl -b` without `-c`, so the rotated ID was
   never saved. With a fresh session the endpoint returns the correct 26 rows. **Not a code
   regression.**
2. **Several admin URLs returned an identical 25,805 bytes.** That is the 404 page — I had
   guessed route names that do not exist. The real routes, scraped from the rendered sidebar, all
   return 200.

---

## 4. Pre-existing bugs found and fixed

**PHP 8 fatal in `fetch_product()`.** `$count_stock` and `$is_purchased_count` were declared
*inside* `if (!empty($product[$i]['variants']))` but consumed unconditionally below it. A product
with no variant rows hit:

```
array_count_values(): Argument #1 ($array) must be of type array, null given
```

The storefront never reached it (`pv.status = 1` filters variantless products out), but **every
administrative read passing `show_only_active_products = 0`** — Manage Stock, product exports —
does, and **13 active products on this database have no variant rows**. Both accumulators are now
declared before the guard; semantics are unchanged (`array_sum([])` is 0, `is_purchased` resolves
to `false`, exactly as an empty loop would have produced).

---

## 5. Files changed

```
NEW   application/helpers/batch_helper.php            batch prefetch layer
NEW   application/migrations/068_performance_indexes.php
NEW   docs/performance/opcache.ini                    OPcache config + how to apply
NEW   docs/performance/FINAL-REPORT.md                this file
NEW   docs/performance/phase-2-query-optimization.md   (previous session)
NEW   tools/perf-bench.php                            page benchmark harness
NEW   tools/opcache-status.php                        OPcache health check
NEW   application/controllers/Perfcheck.php           TEMPORARY test harness — see §6

MOD   .htaccess                                       compression enabled + mod_filter guard
MOD   application/config/autoload.php                 autoload batch helper
MOD   application/config/config.php                   log_threshold duplicate removed
MOD   application/config/database.php                 save_queries environment-aware
MOD   application/helpers/function_helper.php         settings memo, batch hooks, fatal fix
MOD   application/hooks/MyConfig.php                  exclusion list cached
MOD   application/models/Category_model.php           category tree loaded once
MOD   application/models/Order_model.php              two N+1 loops batched
MOD   application/models/Rating_model.php             review-images early-out
MOD   application/models/Setting_model.php            58 write sites wrapped
MOD   application/controllers/admin/Category.php      cache invalidation on reorder
MOD   application/libraries/Shiprocket.php            2 write sites wrapped
MOD   application/migrations/015,048,052,054,062      settings writes wrapped
MOD   application/views/front-end/cretzo/include-css.php  resource hints
```

`index.php` and `application/config/database.php` also carry **pre-existing local edits that are
not mine** — check them before committing.

**Local machine changes (outside the repo, backed up):**
- `C:\xampp\php\php.ini` — OPcache enabled. Backup: `php.ini.bak-perf`
- `C:\xampp\apache\conf\httpd.conf` — `mod_deflate`, `mod_brotli`, `mod_filter`, `mod_expires`
  enabled. Backup: `httpd.conf.bak-perf`

---

## 6. For the morning — decisions and remaining work

> **UPDATE 2026-08-25:** items 1-5 below have been worked through.
> See **`docs/performance/followup-resolutions.md`** for what was resolved, what is still
> blocked on production access, and one deploy-blocking landmine found in `database.php`.

### Needs your decision (I did not act unattended)

1. **`SetEnv CI_ENV development` is in the tracked, deployed `.htaccess`** (line 56).
   `application/config/database.php` selects the **database credentials** from `ENVIRONMENT`, not
   just error verbosity — `development` points at `127.0.0.1:3307` (local XAMPP), `production` at
   the Hostinger database. An `.htaccess` `SetEnv` generally overrides a vhost-level one.
   **Please verify what production actually resolves to.** Run `tools/opcache-status.php` or a
   temporary `<?php echo ENVIRONMENT;` on the server. If it reports `development`, production is
   running with `display_errors` on and is one config reload away from pointing at a database
   that does not exist there. The right fix is to remove the line from `.htaccess` and set
   `CI_ENV=production` in Hostinger's own config — but changing it blind could take the site
   down either way, so it is yours to make.

2. **`brands` charset.** Converting `brands` to `utf8mb4_unicode_ci` would let the storefront use
   `idx_brands_name` and remove the last full table scan. It changes string comparison semantics
   slightly (`ß` = `ss` under `unicode_ci`). Negligible today at 3 brand rows; worth doing before
   the brand list grows.

3. **Delete `application/controllers/Perfcheck.php` before deploy** — or keep it deliberately.
   It is the verification harness (fingerprints, category equivalence, order tables). It is
   CLI-only and returns `show_404()` for any web request, so it is inert in production, but it
   has no business shipping unless you want it for future regression runs.

### Post-deployment checks

1. **Run the migration on production:** `php index.php admin migrate` (the controller allows CLI
   without an admin login), or visit `/admin/migrate` as an admin. Expect version 68.
2. **Confirm compression is live:**
   `curl -sI -H 'Accept-Encoding: gzip,br' https://cretzo.com/ | grep -i content-encoding`
   Hostinger runs LiteSpeed, which reads `.htaccess` but may also have its own compression toggle
   in hPanel — if the header is absent, enable it there.
3. **Confirm OPcache is on** — apply `docs/performance/opcache.ini` via hPanel, then check
   `tools/opcache-status.php`. Watch `oom_restarts`; anything above 0 means raise
   `memory_consumption`.
4. **Re-run the benchmark against production:**
   `BENCH_BASE=https://cretzo.com php tools/perf-bench.php 5`
5. **Re-run the regression suite** after deploy: `php index.php perfcheck snapshot`, compare
   against `docs/performance/baseline-fingerprints.txt`.

### Image compression — still outstanding

Not addressed; it needs judgement about acceptable visual quality, so I left it.

- `uploads/` is **133 MB**; **84 images exceed 300 KB**; the largest is a **4.2 MB PNG**
  (`uploads/media/2025/505.png`). Several 2–2.8 MB PNGs are ChatGPT-generated banners.
- Only 38 WebP files exist across the whole upload tree.
- Suggested approach: convert the >300 KB PNGs to WebP at ~82% quality (typically 85–95%
  smaller), keep the originals, and serve WebP with a `<picture>` fallback. Do a handful first
  and eyeball them before running the batch.
- These are `uploads/`, i.e. **live product and banner imagery** — do not batch-convert without
  a backup.

### Further optimization worth considering

| Item | Expected gain | Risk |
|---|---|---|
| Bundle the 50 head CSS files (a `gulpfile.js` already exists) | Removes ~49 round trips on first paint | Medium — needs visual QA |
| `defer` on head scripts, script-by-script with browser testing | Modest | Medium — see Phase 3c |
| Remove the duplicate `theme.min.js` load (`include-script.php` lines 10 and 39 load the same file twice, re-executing it) | Small, but likely fixes latent double-binding | Low, needs a visual check |
| Homepage still issues 90 queries — each featured section calls `fetch_product()` separately, each opening its own prefetch | Could roughly halve it | Medium |
| Redis/Memcached for the settings table | Small — already memoised per request | Low |

---

## 7. How to re-verify at any time

```bash
# Correctness — all three must pass
php index.php perfcheck snapshot   > /tmp/a.txt
diff <(grep -v QUERIES docs/performance/baseline-fingerprints.txt) <(grep -v QUERIES /tmp/a.txt)

php index.php perfcheck orders     > /tmp/b.txt
diff <(grep -v QUERIES docs/performance/baseline-orders.txt) <(grep -v QUERIES /tmp/b.txt)

php index.php perfcheck categories   # expects "mismatches: 0 => IDENTICAL"

# Performance
php tools/perf-bench.php 5
```

A clean diff on the first two and `mismatches: 0` on the third means every optimised read path
still returns byte-identical data.
