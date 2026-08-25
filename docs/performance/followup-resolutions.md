# Follow-up items — resolutions

Addresses the five "things waiting on you" items from `FINAL-REPORT.md` §6.
Image compression is explicitly out of scope and untouched.

| # | Item | Status |
|---|---|---|
| 1 | `SetEnv CI_ENV development` | **PARTLY RESOLVED.** The deploy-blocking landmine is **FIXED** (see §1b). The CI_ENV value itself still needs one check on the server. |
| 2 | Run migration on production | **BLOCKED — no production access.** Exact commands below. |
| 3 | Delete `Perfcheck.php` | **DONE & VERIFIED** |
| 4 | `brands` charset mismatch | **DONE & VERIFIED** (migration 069) |
| 5 | Apply php.ini / httpd.conf to production | **PARTLY MOOT — premise corrected.** Compression already live; OPcache needs hPanel. |

---

## 1. CI_ENV — what production actually resolves to

### What I checked

| Source | Finding |
|---|---|
| `index.php:56` | `define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development');` — **the fallback is `development`** |
| `origin/main:.htaccess` line 56 | contains `SetEnv CI_ENV development` — this **is** deployed |
| `origin/main:application/config/database.php` | see below |
| All `application/logs/*` `$_SERVER` dumps | every one is `HTTP_HOST=localhost`, `DOCUMENT_ROOT=C:/xampp/htdocs` — **all local, no production evidence** |
| Live site `https://cretzo.com/` | HTTP 200, renders fully, DB clearly connected |
| Live response headers | `X-Powered-By: PHP/8.0.30`, `platform: hostinger`, `panel: hpanel`, `Server: hcdn` |

### The answer to your question: production is on PRODUCTION credentials

This is **certain**, and the reason is not the one either of us assumed. On `origin/main`
— the branch production deploys — `database.php` reads:

```php
if (ENVIRONMENT === 'development') {
    $t_database_name = 'u554344800_cretzo_new';   // <-- PRODUCTION credentials
    $t_username      = 'u554344800_cretzo_new';
    $t_password      = 'Geet@cretzo123';
} elseif (ENVIRONMENT === 'production') {
    $t_database_name = 'u554344800_cretzo_new';   // <-- the SAME production credentials
    $t_username      = 'u554344800_cretzo_new';
    $t_password      = 'Geet@cretzo123';
}
$db['default'] = array('hostname' => 'localhost', ... );   // hardcoded, no port key
```

**Both branches are identical.** Production connects to the production database no matter
which environment it resolves to. That is why the site works, and it is why the `SetEnv`
line has never caused a visible problem.

### What remains ambiguous, and why I stopped

I could not determine with certainty whether `ENVIRONMENT` evaluates to `development` or
`production` on the server. The evidence points strongly at **`development`**:

- The deployed `.htaccess` sets it to `development`.
- Even if Hostinger's LiteSpeed/CDN stack ignores `SetEnv`, `index.php`'s fallback is *also*
  `development`.
- So it is `production` **only if** Hostinger has an explicit `CI_ENV=production` variable set
  in hPanel — which I cannot inspect from here.

I tried to settle it empirically by looking for `display_errors` leakage (on in `development`,
off in `production`), but the homepage produced no PHP notices, so the test was inconclusive
rather than negative. I did not go hunting for a URL that would force an error on your live
site.

**One command settles it.** Run on the server (or drop the file in the web root and fetch it):

```bash
php -r 'echo (isset($_SERVER["CI_ENV"]) ? $_SERVER["CI_ENV"] : "(unset -> defaults to development)"), PHP_EOL;'
```
```php
<?php echo ENVIRONMENT;   // in a temporary file loaded through index.php's bootstrap
```

If it prints `development`, then production is currently running with **`display_errors = 1`**
and **`db_debug = TRUE`** — meaning a database error would print the failing SQL to visitors.
That is a real security/professionalism issue independent of performance.

### The landmine — ~~please read before merging~~ **FIXED, see §1b below**

> **RESOLVED.** What follows describes the hazard as it stood before the fix. It is kept
> for context. See **§1b** for what was actually done — local credentials now live in a
> gitignored `application/config/development/database.php`, so the tracked file can no
> longer carry them.

`application/config/database.php` in **my working tree** (an uncommitted local edit that was
already there when I started — not mine) points the `development` branch at local XAMPP:

```php
if (ENVIRONMENT === 'development') {
    $t_database_name = 'cretzo_db';
    $t_username      = 'root';
    $t_password      = '';
    $t_hostname      = '127.0.0.1';
    $t_port          = 3307;
}
```

If that edit is ever committed and merged to `main`, and production resolves to `development`
(which is the likely case), **production will immediately try to connect to `127.0.0.1:3307`
as `root` with no password and the site will go down.**

Right now this is safe only because the change is uncommitted. Verified:

```
git diff --stat HEAD -- application/config/database.php   ->  22 insertions, 11 deletions
git cat-file -p HEAD:application/config/database.php      ->  still the production credentials
```

**Do not `git add -A` on this repo without checking `database.php`.**

### What I recommended at the time (option 3 was subsequently implemented — see §1b)

The root problem is that a single tracked file decides which database the app talks to, and
the *unsafe* value is the default. Three options, in order of preference:

1. **Invert the default.** Change `index.php` to default to `production` when `CI_ENV` is
   absent, remove `SetEnv CI_ENV development` from the tracked `.htaccess`, and have local dev
   set `CI_ENV=development` in an untracked way (a local-only `.htaccess`, or XAMPP vhost
   `SetEnv`). Then "someone forgot to configure the environment" fails safe.
2. **Keep the branches identical on `main`** (the current accidental safety net) and make it
   deliberate and commented, so nobody "helpfully" restores local credentials to the dev branch.
3. **Move credentials out of the tracked file entirely** — read them from environment
   variables, with `database.php` containing no secrets. This also gets
   `Geet@cretzo123` out of git history going forward.

Option 3 was implemented (see §1b), in the form CodeIgniter natively supports. Options 1 and 2
remain open and still depend on the unresolved CI_ENV question.

---

## 2. Migration on production — blocked, commands below

I have no route to the production database: `database.php` specifies `hostname => 'localhost'`,
which from this machine means *this* machine, and Hostinger does not expose MySQL remotely by
default. I did not attempt to connect.

**Important sequencing:** migrations 068 and 069 live on `Kaif_dev`. They are **not on `main`**,
so running the migrator on production today would do nothing — it is already at whatever `main`
provides. The code must be deployed first.

```bash
# 1. Verify database.php is NOT carrying the local-XAMPP credentials (see §1 landmine)
git diff origin/main -- application/config/database.php

# 2. Merge and deploy Kaif_dev -> main through your normal Hostinger git deploy

# 3. On the server (SSH), from the site root:
php index.php admin migrate
#    Expected output: "Migration Successfully"

# 4. Prove it applied:
mysql -u u554344800_cretzo_new -p u554344800_cretzo_new -e "SELECT version FROM migrations;"
#    Expected: 69
```

Or, as a logged-in admin in the browser: `https://cretzo.com/admin/migrate`
(the controller allows CLI without a login; the browser route requires an admin session).

Both migrations are additive and idempotent — 068 creates indexes only if absent, 069 skips if
the collation already matches. Re-running them is safe.

---

## 3. `Perfcheck.php` deleted — verified

- **Reference check:** the only mentions anywhere were in `docs/performance/*.md`. No PHP, no
  JS, no route in `application/config/routes.php`.
- **Deleted:** `application/controllers/Perfcheck.php`
- **Preserved** at `tools/regression/Perfcheck.php.harness` with a README. That path is outside
  CodeIgniter's routing tree so it is not web-executable, and the file keeps its
  `defined('BASEPATH') or exit` guard. Restore it into `application/controllers/` when you want
  to re-run the suite, then delete it again.

**Verified after deletion:**

| Check | Result |
|---|---|
| App boots | homepage HTTP 200 |
| `/perfcheck` | now returns the same "Page Not Found" page as any bogus URL |
| CLI route `php index.php perfcheck snapshot` | route gone (renders 404 page) |
| 14-page benchmark | **553 queries, all 14 pages HTTP 200** — unchanged from before removal |

> Noted in passing: this app serves its 404 page with **HTTP 200**, for every unknown URL. That
> is pre-existing and unrelated to this work, but it is an SEO problem worth a ticket.

---

## 4. `brands` charset mismatch — fixed and verified

### The statement run

```sql
ALTER TABLE `brands` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Applied as **migration `069_brands_charset_alignment.php`** so it is reproducible on production
and version-tracked.

### Which column, and why that direction

| Column | Before | After |
|---|---|---|
| `products.brand` | `utf8mb4 / utf8mb4_unicode_ci` | **unchanged** |
| `brands.name` | `utf8 / utf8_general_ci` | `utf8mb4 / utf8mb4_unicode_ci` |
| `brands.slug`, `image`, `row_order` | `utf8 / utf8_general_ci` | `utf8mb4 / utf8mb4_unicode_ci` |

**`brands` was converted, not `products`**, because:

- utf8mb4 is a strict **superset** of MySQL's 3-byte `utf8`. Going `utf8 -> utf8mb4` is
  **lossless** — every existing value stays valid.
- The reverse is a **narrowing** conversion: any 4-byte character already stored in
  `products.brand` (an emoji, a rarer CJK ideograph) would be corrupted or rejected. That is
  silent data loss on a live table.
- `products` is also far larger (291 rows vs 3), and the rest of the schema — `products`,
  `categories`, `product_variants`, `product_rating` — is *already* `utf8mb4_unicode_ci`, so
  this moves `brands` **towards** the house standard.
- The whole table was converted rather than just `name`: leaving `slug`/`image` on the old
  charset would just relocate the same trap.

The collation had to match **exactly** — `utf8mb4_general_ci` would still be a mismatch against
`utf8mb4_unicode_ci` and would still block the index.

> Side effect worth recording: MySQL auto-promoted `brands.image` from `TEXT` to `MEDIUMTEXT`.
> `CONVERT TO` does this to preserve the same *character* capacity when bytes-per-char grows.
> It is a widening; no data loss.

### EXPLAIN — before and after

```
BEFORE (post-068, pre-069)                 AFTER (post-069)
b  type=ALL  possible_keys=NULL            b  type=ALL  possible_keys=idx_brands_name
   key=NULL  rows=3                           key=NULL  rows=3
```

`possible_keys` went from **`NULL`** to **`idx_brands_name`** — the index was not even a
*candidate* before, and now it is. That is the definitive proof the mismatch is gone.

The optimizer still chooses a scan because `brands` holds **3 rows**, where scanning is
genuinely cheaper than index lookups. That is the correct plan, not a leftover defect. Proven
two ways:

```
-- 1. Forced: the index is now genuinely usable
EXPLAIN ... LEFT JOIN brands b FORCE INDEX (idx_brands_name) ON p.brand = b.name
  ->  b  type=ref  key=idx_brands_name  key_len=767  rows=1

-- 2. Scale test: 1,003-row copy of brands, optimizer left to choose freely
EXPLAIN ... LEFT JOIN _brands_scale_test b ON p.brand = b.name
  ->  b  type=ref  key=idx_brands_name  key_len=767  rows=1     (not 1,003)
```

So the fix pays off automatically as the brand list grows. (The temporary table was dropped.)

`key_len=767` is exactly at the limit — which is why migration 068's 191-character prefix was
the right choice; 256 characters would have failed.

### Regression — everything that compares brand strings

I built a dedicated fingerprint set covering the whole brand comparison surface, captured it
**before** the ALTER and re-ran it after:

| Fingerprint | Covers |
|---|---|
| `brands/join_products` | the `p.brand = b.name` join itself, with per-brand product counts |
| `brands/order_by_name` | `ORDER BY name` — collation-dependent sorting |
| `brands/equality_probes` | 8 case-variant probes against `brands.name` (`Cretzo`/`cretzo`/`CRETZO`, …) — this is what `is_unique[brands.name]` in `admin/Brand.php` relies on |
| `brands/product_brand_probes` | the same 8 probes against `products.brand` |
| `brands/filter_Cretzo` / `_Adidas` / `_Decathlon` | brand-filtered listings through the real `fetch_product()` path |
| `brands/filter_multi` | the multi-brand `where_in` path |
| `brands/get_brands` | `Brand_model::get_brands()` |

**Result: all identical.** Baseline stored at `docs/performance/baseline-brands.txt`.

Plus the standing suite:

| Check | Result |
|---|---|
| 123 function fingerprints | **PASS** — identical |
| Order tables JSON | **PASS** — identical |
| Category tree, 83 nodes | **PASS** — 0 mismatches |
| Brand-touching pages (`/`, `/products`, `?brands=Cretzo`, `?brands=Cretzo\|Adidas`, `/brands`, product detail) | all HTTP 200, no PHP errors |
| Admin `/admin/brand`, `/admin/home`, seller portal | HTTP 200 |
| New PHP errors in log | **0** |

**Behaviour change, stated honestly:** `utf8_general_ci` and `utf8mb4_unicode_ci` are both
case-insensitive, so brand-name uniqueness validation is unaffected. They differ in full
Unicode collation — `unicode_ci` implements UCA, so it treats `ß` = `ss` and `æ` = `ae` as
equal where `general_ci` does not. All three brand names are ASCII, so there is no practical
difference today. Recorded so it is not discovered by surprise later.

---

## 5. php.ini / httpd.conf on production — premise needs correcting

### The diffs you asked for

These are the *complete* changes I made locally. `*.bak-perf` is the **pre-change** state.

**`php.ini`** — 15 lines:

```diff
-;zend_extension=opcache
+zend_extension=opcache

+; ---- Cretzo performance work (see docs/performance/opcache.ini) ----
+[opcache]
+opcache.enable=1
+opcache.enable_cli=0
+opcache.memory_consumption=192
+opcache.interned_strings_buffer=16
+opcache.max_accelerated_files=20000
+opcache.validate_timestamps=1
+opcache.revalidate_freq=2
+opcache.save_comments=1
+opcache.enable_file_override=0
+opcache.jit=off
+opcache.jit_buffer_size=0
```

**`httpd.conf`** — 4 lines:

```diff
-#LoadModule brotli_module modules/mod_brotli.so
+LoadModule brotli_module modules/mod_brotli.so
-#LoadModule deflate_module modules/mod_deflate.so
+LoadModule deflate_module modules/mod_deflate.so
-#LoadModule expires_module modules/mod_expires.so
+LoadModule expires_module modules/mod_expires.so
-#LoadModule filter_module modules/mod_filter.so
+LoadModule filter_module modules/mod_filter.so
```

### Why these cannot be "applied to production" as written

Both `.bak-perf` files are backups of **my local XAMPP install** (`C:\xampp\...`). They are not
production files, and there is nothing on production to diff them against:

- **`httpd.conf` does not exist on production.** The live headers show `Server: hcdn` with
  `platform: hostinger` / `panel: hpanel` — Hostinger's CDN in front of a managed stack. There
  is no Apache config file you can edit; module loading is not yours to control.
- **`php.ini` is managed through hPanel**, not as a file in the deployment.

### Compression — already live on production, no action needed

I checked directly:

```
Accept-Encoding: gzip, deflate, br   ->  Content-Encoding: br
Accept-Encoding: gzip                ->  Content-Encoding: gzip
```

Hostinger's CDN is already compressing. The `.htaccess` compression block from the last pass is
still worth deploying as an **origin-level fallback** (it applies if the CDN is bypassed or
disabled), and it now carries the `mod_filter` guard fix, so it is safe on any server:

```apache
<IfModule mod_filter.c>          <-- outer guard: AddOutputFilterByType comes from mod_filter,
    <IfModule mod_brotli.c>  ...      NOT mod_deflate. Guarding on mod_deflate alone caused
    <IfModule mod_deflate.c> ...      HTTP 500 on every request during the last pass.
</IfModule>
```

### OPcache — the one thing that still needs doing, by you

Cannot be verified or enabled remotely.

1. hPanel → **Advanced → PHP Configuration → PHP Options** → enable `opcache`.
2. Apply the values from **`docs/performance/opcache.ini`**.
3. Verify with `tools/opcache-status.php` (must run through the **web** SAPI —
   `opcache.enable_cli` is deliberately off).

> Production runs **PHP 8.0.30**; local runs 8.2.12. Every directive in `opcache.ini` is valid
> on 8.0, including `opcache.jit=off`. No changes needed for the version gap.

### Production performance baseline (for comparison after you deploy)

Captured from the live site, current `main` code — i.e. **without** any of the Phase 2/3 work:

| Page | Total | `x-hcdn-upstream-rt` (origin PHP time) |
|---|---|---|
| `/` | 0.763 s | 0.278 s (3.181 s on a cold, uncached hit) |
| `/products` | 1.314 s | **1.015 s** |
| `/sellers` | 0.329 s | 0.067 s |
| `/login` | 0.224 s | 0.022 s |
| `/home/faq` | 0.291 s | 0.038 s |

`x-hcdn-upstream-rt` is the useful number — it is origin time with the CDN excluded. `/products`
at ~1.0 s is the page this work takes from 976 queries to 52.

---

## Local machine state

| File | State |
|---|---|
| `C:\xampp\php\php.ini` | OPcache enabled. Backup: `php.ini.bak-perf` |
| `C:\xampp\apache\conf\httpd.conf` | 4 modules enabled. Backup: `httpd.conf.bak-perf` |
| `cretzo_db` | migrations **068** and **069** applied, version **69** |
| Temp objects | `_brands_scale_test` dropped; `_opcache_probe.php` / `_jwt.php` removed from htdocs |

---

## 1b. The `database.php` landmine — FIXED

Applied after the initial write-up. The hazard described in §1 is now structurally
impossible.

### What was done

CodeIgniter already supports per-environment database config, and this project already
uses it (`application/config/testing/database.php`). From `system/database/DB.php:58`:

```php
if ( ! file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/database.php')
    && ! file_exists($file_path = APPPATH.'config/database.php'))
```

CI loads `config/<ENVIRONMENT>/database.php` in preference to the base file. So:

1. **Created `application/config/development/database.php`** holding the local XAMPP
   credentials (`cretzo_db`, `root`, no password, `127.0.0.1:3307`).
2. **Added `application/config/development/` to `.gitignore`** — verified with
   `git check-ignore`, which reports it matched at `.gitignore:27`.
3. **Restored the tracked `application/config/database.php`** so every branch resolves to
   the production credentials, and added an `else` fallback so an unexpected `CI_ENV`
   value no longer leaves the `$t_*` variables undefined.

The two wanted changes were kept: `save_queries => (ENVIRONMENT !== 'production')` and the
`$t_hostname` / `$t_port` structure.

### Why this is safe regardless of the unresolved CI_ENV question

`application/config/development/` is gitignored, so it is never committed and never
deployed. Production therefore never finds it, falls back to the tracked base file, and
gets the production credentials — **whichever value ENVIRONMENT takes.** Verified by
hiding the directory and resolving all three cases:

```
ENVIRONMENT=development  -> config/database.php   host=localhost:3306  db=u554344800_cretzo_new
ENVIRONMENT=production   -> config/database.php   host=localhost:3306  db=u554344800_cretzo_new
ENVIRONMENT=typo_value   -> config/database.php   host=localhost:3306  db=u554344800_cretzo_new
```

That is the property that mattered, because whether production resolves to `development`
or `production` is still unconfirmed.

The `'port' => 3306` key is newly present but **inert on production**: mysqli special-cases
the hostname `localhost` to a UNIX socket and ignores the port
(`system/database/drivers/mysqli/mysqli_driver.php:211`). Production resolution is
therefore byte-identical to today's `'hostname' => 'localhost'`.

### Verification

| Check | Result |
|---|---|
| Local still connects to XAMPP | `config/development/database.php` loaded, `127.0.0.1:3307`, `cretzo_db` |
| Local pages | homepage + `/products` HTTP 200, no DB error |
| Production simulation (override hidden) | all 3 ENVIRONMENT values → production credentials |
| 123 function fingerprints | **PASS** — identical |
| Order tables JSON | **PASS** — identical |
| Category tree, 83 nodes | **PASS** — 0 mismatches |
| Brand fingerprints | **PASS** — identical |
| Page sweep (8 storefront + admin + seller) | all HTTP 200, no PHP errors |
| 14-page benchmark | 553 queries, all 200 |
| `git diff origin/main -- database.php` | reviewed line by line; no credential change for production |

### Setting up a new local machine

`application/config/development/database.php` will **not** be in a fresh clone. Recreate it
with local credentials. If it is missing, CI falls back to the base file, which holds
**production** credentials — so do not develop without it.

### Still outstanding on item 1

Only the original question: **what does `CI_ENV` actually resolve to on the server?** It
no longer risks the database, but it still controls `display_errors` and `db_debug`. If it
is `development`, production is showing PHP errors and would print failing SQL to visitors.
That check is Step 0 in the deployment steps.
