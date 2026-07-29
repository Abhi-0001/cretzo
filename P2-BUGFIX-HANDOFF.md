# Cretzo Seller Module — P2 Bug-Fix Handoff

**Purpose of this doc:** hand off the P2 ("broken features, not security") backlog from a prior session into a fresh chat, since that session's context window filled up. Paste/attach this whole file to the new chat and say "work through this list."

**Status as of this handoff (2026-07-29):**
- ✅ **P0 (production-breaking security)** — done, verified live: unauthenticated SQL injection in `seller/chat/search_user`, SQL injection via date filters on Sales Report/Inventory/Media, and exposed `bkp.zip`/`cretzo.zip` archives containing prod DB credentials (git-side cleanup done; **you must still confirm the archives were deleted from the live server and the DB password + `encryption_key` were rotated** — that part only the user can do).
- ✅ **P1 (cross-seller authorization/IDOR)** — all 16 findings fixed and individually verified live (reproduced the attack, confirmed it's blocked, confirmed the legit seller flow still works). Covers `Orders`, `Pickup_location`, `Point_of_sale`, `Product`, `Product_faqs`, `Payment_request`, `Chat`, `Customer`, `Invoice`.
- ⬜ **P2 (this doc)** — broken features, no security impact. Not started.
- ⬜ **P3** — dead code / unused pages. Partially done: a large deletion pass already removed most `-OLD`/`copy`/backup files across the whole codebase (not just seller). Still open: a handful of whole unlinked sections (Invoice, Customer, Pickup Location, Fund Transfer) need a human decision — relink or remove.

**Nothing from this session is committed to git yet.** `git status` will show the P1 changes plus a large unrelated deletion pass. Read `git log`/`git status`/`git diff` first before touching anything, so you don't collide with in-progress work.

**One collision to know about:** the working tree has ~890 uncommitted lines of a separate seller-profile/KYC feature in progress (`seller/Home.php`, `seller/Login.php`, `Seller_model.php`, `profile.php`, `form.js`), plus 3 unrun migrations (`021_seller_personal_details.php`, `022_seller_primary_category.php`, `023_seller_business_details.php`). **Item P2-9 below (the `Seller_model` column-name split) directly overlaps this work — read that code before touching it, since it may already be mid-refactor.**

**Local dev setup:** XAMPP, MySQL on port 3307 (not 3306), db `cretzo_db`, site at `http://localhost/cretzo/`. Query directly with:
```
/c/xampp/mysql/bin/mysql.exe -h 127.0.0.1 -P 3307 -u root cretzo_db -e "..."
```
Test seller login: mobile `7303890593`, password `Test@1234` (user id 7, owns most of the catalog). For any live verification, log in via `POST seller/login/auth` with `identity`, `password`, `type=phone`, then hit the URL with the returned cookie.

**How each item below was established:** every finding was either (a) reproduced live this session, (b) reproduced by a sub-agent audit and independently confirmed by re-reading the code just now (line numbers current as of this handoff), or (c) flagged by the audit but not yet re-verified — marked explicitly. Don't trust a "already confirmed" claim blindly if the surrounding code has changed since — re-check before fixing.

---

## Priority order

Work top to bottom; each entry is mostly independent except where noted.

### P2-1 — Seller approval lists are 100% broken (ambiguous SQL column)
**File:** `application/models/Seller_model.php:538, 555, 677, 799`
```php
$count_res = $this->db->select(...)->where('status', 1)->join('users_groups ug', ...)->join('seller_data sd', ...);
```
**Problem:** both `users` and `seller_data` (joined) have a `status` column. Unqualified `where('status', 1)` is ambiguous.
**Verified:** yes, this session — running the equivalent query directly returns `ERROR 1052 (23000): Column 'status' in where clause is ambiguous`.
**Impact:** the admin "approved sellers" / "pending sellers" (`status=2`) / "deactivated sellers" (`status=0`) list pages all 500.
**Fix:** qualify as `sd.status` (matches the intent — this is filtering on `seller_data.status`, the seller-approval field, not `users.status`) at all 4 sites.

### P2-2 — Seller profile city dropdown always fails
**File:** `application/controllers/seller/Home.php` — the `get_cities_by_district()` AJAX endpoint (~line 452 as of the original audit; re-locate by searching for `select('id,name')->from('cities')` — grep `application/controllers/seller/Home.php` for `cities`).
```php
$cities = $this->db->select('id,name')->from('cities')->where('state_id', $state_id)->where('district_id', $district_id)->...
```
**Problem:** `cities` table's real columns are `city_id`/`city_name`, and it has **no `state_id` column at all** (state is reached via `districts.state_id`, cities only has `district_id`).
**Verified:** yes, this session — `DESCRIBE cities` confirms only `city_id, city_name, district_id, ...`; running the query gets `ERROR 1054: Unknown column 'id' in 'field list'`.
**Note:** a sibling function ~60 lines earlier in the same file was already correctly fixed to use `city_id as id, city_name as name` — this AJAX endpoint was just missed in that pass.
**Fix:** `select('city_id as id, city_name as name')->where('district_id', $district_id)` — drop the `state_id` filter (or join `districts` if you want to also validate state).

### P2-3 — Manage Customers page permanently empty (same schema bug, different file)
**File:** `application/models/Customer_model.php:52, 70, 157, 175` (and the `` `c.name` `` search key at lines 44, 150)
```php
$count_res = $this->db->select(' COUNT(u.id) as `total` ,a.name as area_name,c.name as city_name')->join('cities c', 'u.city=c.id', 'left')...
```
**Problem:** same `cities.id`/`cities.name` vs real `cities.city_id`/`cities.city_name` mismatch as P2-2, in the customer list query.
**Verified:** re-confirmed just now (grep shows the exact broken column references still present at those 4 lines).
**Impact:** `seller/customer/view_customer` 500s — the AJAX data source for Manage Customers.
**⚠️ Important interaction with P1 work done this session:** this session added a `customer_privacy` permission check to this same endpoint (`Customer::view_customer()` in `application/controllers/seller/Customer.php`) that was previously bypassable — full customer PII (email, mobile, wallet balance) was reachable by any seller regardless of permission. **That permission check is now in place, but this endpoint still 500s due to this schema bug — meaning the permission check has never actually been exercised against real data.** When you fix this schema bug, immediately re-verify the permission check still holds (log in as a seller with `customer_privacy=0` in `seller_data.permissions` and confirm they still get blocked, not the data).
**Fix:** same as P2-2 — join on `city_id`, select `c.city_name as city_name`, fix the `` `c.name` `` search key to `` `c.city_name` ``.

### P2-4 — `seller/attribute/` is a white-screen PHP fatal error
**File:** `application/controllers/seller/Attribute.php:4`
```php
class Attribute extends CI_Controller
```
**Problem:** `Attribute` is a reserved class name in PHP 8 (built-in). Declaring a class with this exact name is a fatal error.
**Verified:** yes, this session — `curl http://localhost/cretzo/seller/attribute/` returns HTTP 500 with `Fatal error: Cannot declare class Attribute, because the name is already in use`.
**Context:** this file is an exact functional duplicate of `Attributes.php` (plural), which works fine and is what the sidebar actually links to (`seller/attributes/manage_all`). This singular one isn't linked from anywhere in the UI, but it's still a publicly routable URL that hard-crashes.
**Fix:** simplest is to delete `application/controllers/seller/Attribute.php` entirely (confirm nothing references `seller/attribute/` first — the audit found nothing). If you'd rather keep it for some reason, rename the class to something like `Seller_attribute`.

### P2-5 — Deactivate/Delete buttons on Manage Products do nothing
**Files:** `application/models/Product_model.php` (renders the buttons, ~lines 512-522 as of the original audit — re-locate via grep for `update_active_status` and `delete-product`) + `application/views/seller/pages/tables/manage-product.php` (the page, no click handler)
**Problem:** the buttons' click handlers (`.update_active_status`, `#delete-product`) are defined only in `assets/admin/custom/custom.js`, which **is not loaded on any seller page** (confirmed: `application/views/seller/include-script.php` only loads `pos.js` + `demo.js`). Clicking either button does precisely nothing — no request, no error, no feedback.
**Verified:** browser-verified in the original audit (0 network requests on click); not re-verified this session but the root cause (custom.js absence) was independently re-confirmed by this session's own P1 work (multiple `data-query-params` functions had this exact same "defined only in custom.js" problem and needed page-local re-implementations).
**Fix:** add inline `<script>` handlers directly in `manage-product.php`, following the pattern already used successfully elsewhere in this codebase (e.g. `manage_stock.php`, `manage-orders.php`, and this session's fix to `manage-attribute.php` — see P2-6 below for the umbrella issue). Something like:
```js
$(document).on('click', '.update_active_status', function () {
    var id = $(this).data('id'), table = $(this).data('table'), status = $(this).data('status');
    $.get(base_url + 'seller/home/update_status', {table: table, id: id, status: status}, function (res) {
        $('#products_table').bootstrapTable('refresh');
    });
});
$(document).on('click', '#delete-product', function () {
    var id = $(this).data('id');
    // confirm, then $.get(base_url + 'seller/product/delete_product?id=' + id, ...)
});
```
**Also note:** `#delete-product` is emitted as an `id` attribute **once per table row** (should be a `class`), so even a naive `getElementById`/`$('#delete-product')` selector would only ever see the first row. Fix that too while you're in `Product_model.php` (change `id="delete-product"` → `class="delete-product"` and update the selector).

### P2-6 — Product FAQs and Ratings tabs on Manage Products always show empty
**File:** `application/views/seller/pages/tables/manage-product.php` — two `<table>`s with `data-query-params="queryParams"` and `data-query-params="ratingParams"`
**Problem:** same root cause as P2-5 — `queryParams`/`ratingParams` are defined only in `custom.js`, not loaded on seller pages, so `window.queryParams` is `undefined`. bootstrap-table can't resolve it to a function, so it appends the **literal string** `?queryParams` to the request instead of `?limit=10&offset=0&sort=...`. The backend then throws a PHP "Undefined array key" warning that corrupts the JSON response, and the table just shows "No matching records found" forever.
**Verified:** browser-confirmed in the original audit (`GET seller/product/get_faqs_list?queryParams` → invalid JSON due to a PHP warning being prepended). This exact bug class was independently confirmed and fixed by this session in a different file (`manage-attribute.php` had `?queryParams` cause a 150-requests-in-2-seconds runaway loop, not just an empty table — worth checking whether these two tables have the same runaway-loop risk once you're testing them).
**Fix:** add a local `function queryParams(p) { return {limit:p.limit, sort:p.sort, order:p.order, offset:p.offset, search:p.search}; }` and a `ratingParams` counterpart, inline in `manage-product.php`'s own `<script>` block — copy the pattern already working in `manage_stock.php`/`manage-orders.php`/`sales-report.php`.

### P2-7 — A JavaScript error fires on literally every seller page
**File:** `application/views/seller/include-footer.php` (~lines 22-33 as of the original audit)
**Problem:** a global "media picker" modal (`#dropzone`, `#upload-files-btn`, `#upload-media`, a `media-upload-table`) is present in the footer of every seller page. The Dropzone widget auto-initializes with no `action`/url configured, throwing an uncaught `Error: No URL provided.` on page load — on all ~30 seller pages. Separately, its table's `data-query-params="mediaParams"` has the exact same undefined-function bug as P2-6, and the "Choose Media"/"Upload" buttons have no click handler at all (same custom.js-not-loaded root cause).
**Verified:** browser-confirmed in the original audit; not re-run this session.
**Context:** the newer product-creation form (`application/views/seller/pages/forms/product.php` + `assets/seller/js/product.js`) has its own, working, self-contained image uploader. This footer modal looks like a leftover from before that was built, and doesn't appear to be triggered from anywhere (`data-target="#media-upload-modal"` — the audit found 0 elements with that trigger anywhere in the seller views).
**Fix (recommended):** just delete the dead media-modal markup from `include-footer.php` — it's unreachable and only costs every page a JS error and a wasted request. If you're not confident it's truly unused, the safer alternative is to give the dropzone a real `action` URL and define `mediaParams` — but deletion is almost certainly correct here; confirm no view does `data-target="#media-upload-modal"` before removing.

### P2-8 — Search/sort/pagination silently dead on 5 seller tables
**Files/pages:** `seller/taxes/` (`manage-taxes.php`), `seller/attribute_set/` (`manage-attribute-set.php`), `seller/attribute_value/` (`manage-attribute-value.php`), `seller/pickup_location/manage_pickup_locations` (`manage-pickup_location.php`), `seller/customer/` (`manage-customer.php`)
**Problem:** identical root cause to P2-6 — each of these tables uses `data-query-params="queryParams"` with no local definition, so typing in the search box or clicking a column header sends `?queryParams` literally and changes nothing.
**Verified:** browser-confirmed in the original audit for all 5 (captured the literal `?queryParams` request for each when interacting with search).
**Fix:** same five-line `queryParams(p)` function, added inline to each of the 5 views. This is mechanical — same fix, five files.

### P2-9 — Seller profile data silently dropped on save (⚠️ overlaps in-progress work)
**File:** `application/models/Seller_model.php:37-95` (`add_seller()`)
**Problem:** the write path inserts/updates using column names `shop_name`, `account_holder_name`, `pan`, `ifsc`, but every read path (confirmed at lines ~237, ~374, ~587 as of the original audit) reads `store_name`, `account_name`, `pan_number`, `tax_number`. **Both column families exist in the `seller_data` table** (confirmed via `SHOW COLUMNS`), so this isn't a typo that errors — it's a silent split where half the form's fields get written to columns nobody ever reads back.
**Verified:** flagged by the original audit as "MEDIUM-HIGH data loss," not independently re-verified this session (didn't want to touch `Seller_model.php` mid-handoff given the uncommitted profile work sitting on top of it).
**⚠️ Before touching this:** the working tree already has substantial uncommitted changes to exactly this file (`Seller_model.php`) as part of an in-progress seller personal-details/KYC feature (see the 3 unrun migrations mentioned at the top of this doc). **Read the current, actual state of `add_seller()` first** — this bug may have already been partially or fully fixed by that in-progress work, or the column split may be intentional groundwork for the new feature. Don't blindly "fix" this without understanding what that other work is doing first.

### P2-10 — Bulk CSV product import: silent partial writes
**File:** `application/controllers/seller/Product.php` — the `bulk_upload()`/`process_bulk_upload()` methods (~lines 985-1170 as of the original audit; these line numbers will have shifted from this session's P1 edits to the same file — re-locate by searching for `$this->db->insert('products', $data)`).
**Problem:** three `insert()` calls' return values are ignored, there's no transaction wrapping the per-row inserts, and the response is hardcoded to `'Products uploaded successfully!'` regardless of what actually happened. A row that fails partway through leaves an orphaned `products` row with no variants, while the seller is told everything worked. Separately, several `$row[N]` array accesses have no `isset()` guard, so a CSV with fewer columns than expected triggers PHP 8.2 "Undefined array key" warnings that print into the response body before the JSON, corrupting it.
**Verified:** flagged by original audit via full code read, not independently re-run this session (would need a real CSV upload to reproduce cleanly).
**Fix:** wrap the per-row logic in `$this->db->trans_start()` / `trans_complete()` / check `trans_status()`; check each `insert()`'s return; default missing CSV columns with `$row[$n] ?? ''` instead of raw indexing.

### P2-11 — `Media_model::fetch_media()` silently returns everything
**File:** `application/models/Media_model.php` (~lines 43-121 as of original audit)
**Problem:** `$offset`/`$limit`/`$sort` are only assigned inside `if (isset($_GET['offset']))` etc., but there's no `else` branch initializing them first — so when those GET params are absent, `$offset`/`$limit` stay `null`. `->limit(null, null)` is a silent no-op in CodeIgniter's query builder, so the entire `media` table gets returned instead of a paginated slice, plus three PHP "Undefined variable" warnings get printed into the JSON response.
**Verified:** flagged by original audit, not re-run this session — quick to verify: `curl` the seller media list endpoint with no query params and check the response starts with a PHP warning instead of `{`.
**Fix:** initialize `$offset = 0; $limit = 10; $sort = 'id';` before the `$_GET` checks (matches the pattern already used correctly in most of the other `*_model.php` files in this codebase).

### P2-12 — `seller/transaction/view-transaction` silently renders the wrong page
**File:** `application/controllers/seller/Transaction.php:33` (`view_transaction()`)
```php
$this->data['main_page'] = TABLES . 'transaction';
```
**Problem:** `application/views/seller/pages/tables/transaction.php` does not exist. `application/views/seller/template.php` silently falls back to `forms/home` (the dashboard) when the resolved view file is missing — so this route returns HTTP 200 with the title "View Transaction" but the actual seller dashboard as the body. No error anywhere.
**Verified:** browser-confirmed in the original audit (title tag says "View Transaction", body is the Home dashboard widgets).
**Fix:** either build the missing `tables/transaction.php` view (check `seller-wallet.php` for the pattern — that's likely the intended view for this data), or delete `view_transaction()` if it's genuinely unused. Also worth making `template.php`'s fallback **log or 404** instead of silently substituting the dashboard — that silent-fallback behavior is what let this go unnoticed and may be masking other missing-view bugs elsewhere.

### P2-13 — `seller/fund_transfer/` queries the wrong module entirely
**Files:** `application/controllers/seller/Fund_transfer.php:25` + `application/views/seller/pages/tables/manage-fund-transfers.php`
```php
// Fund_transfer.php:25
$this->data['fetched_data'] = fetch_details(['id' => $_GET['edit_id'], 'status' => '1'], 'delivery_boys');
```
**Problem:** the `fetch_details($table, $where, ...)` argument order is swapped — an array landed in the `$table` slot and a string in `$where`. Separately, `manage-fund-transfers.php`'s table points at `delivery_boy/fund_transfer/view_fund_transfers/...`, and `Fund_transfer.php` itself renders `delivery_boy/template` and redirects unauthenticated users to `delivery_boy/login`, not `seller/login`. This whole controller looks like a copy-paste from the delivery-boy module that was never adapted for sellers.
**Verified:** flagged by original audit via code read; browser-confirmed the page renders chrome-less with a permanently-empty table and throws `google.translate.TranslateElement is not a constructor`.
**Fix:** this one needs a decision, not just a patch — either (a) properly build a seller-scoped fund-transfer feature (fix the controller to use `seller/template`, fix the swapped `fetch_details()` call, point the table at a real seller endpoint), or (b) delete `Fund_transfer.php` + `manage-fund-transfers.php` entirely if sellers were never supposed to have this feature. Flag to the user — this reads like it might not be an intentional seller feature at all.

### P2-14 — View Product ratings table posts to the admin controller
**File:** `application/views/seller/pages/view/products.php:166` (or nearby — grep for `admin/product/get_rating_list`)
```php
data-url="<?= base_url('admin/product/get_rating_list') ?>" ... data-query-params="product_rating_query_params"
```
**Problem:** should be `seller/product/get_rating_list` (which exists, and — as of this session's P1 work — is now correctly seller-scoped). Hitting the admin endpoint with a seller session redirects to the admin login page (HTML), which bootstrap-table can't parse. `product_rating_query_params` also has the same undefined-function problem as P2-6.
**Verified:** static-only in the original audit (the table only renders for a product that already has ratings; the audit couldn't easily find one to click through). Quick to verify now: seed a `product_rating` row for one of the test seller's products (see this session's testing pattern — insert into `product_rating`, test, then delete the row) and load `seller/product/view-product?edit_id=<that product>`.
**Fix:** change the URL to `seller/product/get_rating_list` and add the missing `product_rating_query_params` function inline.

### P2-15 — Duplicate element IDs
**Locations:**
- `Product_model.php` — `id="delete-product"` emitted once per row on Manage Products (also blocks the fix in P2-5 from working correctly on any row but the first)
- `seller/point_of_sale/` — `#change` × 15
- `seller/media/` — `#dropzone` × 2, `#upload-files-btn` × 2 (the second copy is the dead footer modal from P2-7)
- `seller/orders/`, `seller/product/`, `seller/product_faqs/`, `seller/manage_stock` — duplicate `#exampleModalLongTitle` / `#error_box`
**Verified:** browser-confirmed in the original audit via a live DOM `[id]` scan; not re-run this session.
**Fix:** convert the per-row ones (`delete-product`) to classes; de-duplicate the modal-title/error-box IDs (rename one instance of each, update the corresponding JS selector).

### P2-16 — Dead view referencing a nonexistent controller method
**File:** `application/views/seller/pages/tables/manage-digital-product-order.php`
```php
data-url="<?= base_url('seller/orders/view_digital_product_order_items') ?>"
```
**Problem:** `Orders::view_digital_product_order_items()` doesn't exist in the seller controller (only the admin one has it). No seller controller ever sets `main_page` to this view, so it's unreachable through normal navigation — but if reached directly (`seller/orders/manage-digital-product-order`), it just 404s with three additional JS console errors on top.
**Verified:** static-only in the original audit; not re-run this session (still exists on disk per the file check above).
**Fix:** delete the view file — it has no live entry point and no working backend.

### P2-17 — Broken asset path (cosmetic)
**File:** `assets/admin/css/star-rating.min.css` (~line 17 as of original audit)
```css
background: url('/assets/loading.gif') top left no-repeat;
```
**Problem:** root-absolute path, missing the `/cretzo` base — 404s in the console, star-rating widget shows a broken image instead of its loading spinner.
**Verified:** browser-confirmed (404 on `http://localhost/assets/loading.gif`) in the original audit.
**Fix:** change to a relative path (`url('../loading.gif')`, adjust `../` count to match the actual asset location) so it resolves under whatever base path the app is served from.

### P2-18 — Miscellaneous smaller items (grouped — each is quick)
- **`application/models/Payment_request_model.php:117`** — rejecting an already-rejected withdrawal re-credits the wallet each time it's rejected again (no check on current status before crediting). Repeatable balance inflation. Add a guard: only credit if the request's current status isn't already the target status.
- **`application/models/Category_model.php:53-55, 117`** — calls `count_all_results()` **after** `->get()`, which CodeIgniter's query builder has already reset by then, so the count query runs with no WHERE/JOIN at all. Verified live (in the original audit) that the seller category tree reports `total=79` (every category) instead of the real ~9, and `get_seller_categories()` reports the whole `seller_data` row count. Fix: compute the count query the same way `get_sales_list()`-style functions in this codebase do it — build and run the `COUNT()` query as its own separate statement *before* calling `->get()` for the row data, not after.
- **`application/models/Category_model.php:216`** — the *exact same* `or_like`-before-ownership-`where` precedence bug this session fixed in `Payment_request_model.php` (see the P1 summary above for the mechanism — `AND` binds tighter than `OR` in SQL, so an ungrouped `or_like` search escapes a later `AND` scope). Wrap in `group_start()`/`group_end()`, same fix.
- **`application/models/Transaction_model.php:74 → 116/138`** — an unrecognized `?user_type=` value makes `fetch_details()` return empty, so `$group_id` ends up `null`, producing `WHERE ug.group_id = ` (empty) → SQL syntax error. Add a default/fallback when the group lookup comes back empty.
- **`application/models/Transaction_model.php:269`** (`edit_transactions()`) — returns `false` on success and `true` on failure (inverted), and the controller ignores the return value entirely, always printing "Transaction Updated Successfuly" regardless. Fix the inverted return, then make the controller actually check it.
- **`application/models/Product_faqs_model.php:54`** — `if (isset($offset)) $offset = $_GET['offset'];` — this tests the *local* variable `$offset` (which was just default-initialized, so `isset()` is always true) instead of `isset($_GET['offset'])`. Always overwrites `$offset` from `$_GET['offset']`, which is usually undefined → "Undefined array key" warning + `$limit` ends up `null` → the entire FAQ table returns unpaginated. Fix the `isset()` target.
- **`application/models/Seller_subscription_model.php:27`** — compares `end_date` (a `DATE` column, confirmed via `SHOW COLUMNS`) against `date('Y-m-d H:i:s')` (a full datetime). Subscriptions expire roughly a day early because midnight-of-expiry-day already compares as "in the past" against a timestamp with a non-midnight time component. Fix: compare against `date('Y-m-d')` instead, or add `23:59:59` to the comparison, matching the column's actual precision.
- **`application/models/Attribute_model.php:131`** — `count($data['attribute_value'])` throws a PHP `TypeError` (fatal) if a form is submitted with no `attribute_value[]` field at all. Guard with `!empty($data['attribute_value']) ? count(...) : 0`.
- **`application/models/Attribute_model.php:341` / `:360`** — the function signature says `(..., $offset, $limit)` but the body calls `->limit($offset, $limit)` (CodeIgniter's `limit()` signature is `limit($value, $offset)` — reversed). Currently harmless because every caller *also* passes them swapped, so the two bugs cancel out — but it's a landmine for the next person who calls this correctly per the documented signature. Fix the body to match the signature, and fix all callers to match.

---

## Suggested grouping for a fresh session

If you want to batch this rather than doing all 18 in one pass:

1. **Schema-mismatch group (P2-1, 2, 3)** — all the same root cause (a handful of `cities`/`status` column-name bugs), all quick, all backend-only, no UI risk. Good first batch.
2. **The custom.js-dependency group (P2-5, 6, 7, 8)** — all the same root cause (seller pages don't load `assets/admin/custom/custom.js`, so any `data-query-params`/click-handler defined only there is dead). Fix these together since the pattern is identical each time — write the five-line `queryParams` stub once, then paste-and-adapt into each of the ~8 affected views.
3. **Dead/misrouted pages (P2-4, 12, 13, 14, 16)** — mix of "delete this" and "point this URL somewhere else." P2-13 (Fund Transfer) needs a user decision first, flag it before touching.
4. **Data-integrity grab-bag (P2-9, 10, 11, 18)** — independent, do these whenever, but **P2-9 needs the in-progress-work check first**.
5. **Cosmetic (P2-15, 17)** — lowest priority, do last or skip if time-constrained.
