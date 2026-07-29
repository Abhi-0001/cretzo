# Cretzo Seller Module — P3 Dead-Code & Decisions Handoff

**Companion doc to `P2-BUGFIX-HANDOFF.md`** (same repo root). Paste/attach both to a fresh chat if you're doing P2 and P3 together, or just this one if you're only doing cleanup.

**What P3 covers:** unused pages/controllers safe to delete, and — separately — whole sections that are reachable but effectively orphaned, where the right call is a **product decision** (relink it, or remove it) rather than a code fix. That second category is flagged clearly below; don't delete anything in it without asking the user first.

**Status as of this handoff (2026-07-29):** P0 and P1 (security) are done and verified live — see the top of `P2-BUGFIX-HANDOFF.md` for the full rundown. A large deletion pass already ran on this codebase (not by me, by the user or a parallel session) and removed most of the obvious `-OLD`/`copy`/backup files across the *entire* codebase, not just the seller module. This doc reflects what's **left** after that pass, re-checked against the filesystem just now — not a stale copy of the original audit.

**Important nuance vs. the original audit:** several things the original audit called "dead endpoint, safe to delete" turned out, during the P1 security pass, to be **exploitable even though unreachable from the current UI** — they were still publicly routable. Rather than delete those, P1 **hardened** them (added ownership checks, auth guards, injection fixes). They're listed below as "hardened, not deleted" — you can still choose to delete them now that they're safe, but that's optional cleanup, not an open vulnerability.

---

## Already deleted — nothing to do

Confirmed gone from disk just now. No action needed, listed only so you know they've been handled:
```
application/controllers/seller/Login-OLD.php
application/views/seller/include-navbar-OLD.php
application/views/seller/include-sidebar-OLD.php
application/views/seller/pages/forms/home-OLD.php
application/views/seller/pages/forms/product-OLD.php
application/views/seller/pages/forms/profile-OLD.php
application/views/seller/pages/forms/profile1.php
application/views/seller/pages/forms/profile_new.php
application/views/seller/pages/forms/change-password.php
application/views/seller/pages/forms/seller-registration1.php
application/views/seller/pages/forms/seller-registration-old-working.php
application/views/seller/pages/forms/seller-registration_update.php
application/views/seller/pages/forms/seller-registration.php-old
application/views/seller/pages/forms/seller-registration.php-old1
application/views/seller/pages/forms/seller-registration.php-new-developed
application/views/seller/components/personal_details_slider.php
application/views/seller/components/store_details_slider.php
application/views/seller/components/bank_details_slider.php
application/bkp.zip          (also removed from git tracking + added to .gitignore)
cretzo.zip                   (also removed from git tracking + added to .gitignore)
```
Plus a much larger batch of `-OLD` files outside the seller module entirely (admin, front-end, assets, an old `cretzo/` legacy prototype folder) — not itemized here since they're out of scope for this seller-focused effort, but `git status` will show them as deleted.

---

## Still present — safe to delete now, no decision needed

### D-1 — `application/views/seller/signup.php`
Confirmed still on disk. Zero references anywhere in the repo (grepped controllers, views, JS). The live seller signup page is `application/views/seller/pages/forms/seller-registration.php`, reached via `Auth.php:60`. This top-level `signup.php` is a different, older file that nothing loads.
**Action:** delete.

### D-2 — `application/views/seller/pages/tables/manage-digital-product-order.php`
Already covered as **P2-16** in the companion doc (it's a dead view, not a bug — listing it here too for completeness of the cleanup pass). Points at `seller/orders/view_digital_product_order_items`, a method that only exists on the *admin* Orders controller, not the seller one. No seller controller ever sets `main_page` to this view.
**Action:** delete the view file.

That's it for pure "delete, no discussion needed" — everything else below needs either verification-before-deleting or an actual decision from the user.

---

## Needs your decision before touching — do not delete unilaterally

These are whole sections that are **reachable by URL but not linked from the seller sidebar**. Each one represents either an intentionally-hidden feature, an abandoned one, or a copy-paste that was never finished. Ask the user "relink this, or remove it?" for each before acting — the original audit flagged all of these as genuinely ambiguous, not as findings with an obvious right answer.

### Decision-1 — The entire Chat feature
**Files:** `application/controllers/seller/Chat.php` (17 public methods), `application/models/Chat_model.php`, `assets/seller/components-chat-box.js`, `application/views/seller/pages/view/chat.php`
**Current state:** the page (`seller/chat`) has been replaced with a "Chat Coming Soon — use WhatsApp instead" placeholder (confirmed just now — it's a static card with a WhatsApp deep link, no chat UI, no `<script>` tags at all). The JS file that would drive a real chat UI (`components-chat-box.js`) is not loaded by any view. So the entire chat *feature* is off, but all 17 backend methods (`send_msg`, `load_chat`, `switch_chat`, `mark_msg_read`, `delete_msg`, `send_fcm`, etc.) are still live, public URLs.
**What changed since the original audit:** this session's P1 security pass found and fixed a live, unauthenticated SQL injection in this exact controller (`search_user()` — no login check at all, raw SQL, verified by dumping 32 real users with zero credentials), plus a second bug in `load_chat()` where any `type` value other than the literal string `'person'` produced a query with **no participant filter at all**, returning every private message ever sent on the platform to any logged-in user. Both are now fixed — the whole controller is hardened, not just patched-over. So this is no longer an open vulnerability either way.
**The actual decision:** is chat coming back as a real feature, or is WhatsApp-redirect the permanent design? If permanent: delete `Chat.php`, `Chat_model.php`, `components-chat-box.js`, and the now-unused route entries, and stop maintaining a hardened-but-pointless attack surface. If chat is coming back: keep everything as-is (it's already secured) and this becomes a feature-completion task, not a cleanup task.

### Decision-2 — The Invoice section
**Files:** `application/controllers/seller/Invoice.php`, `application/views/seller/pages/view/invoice.php`, `application/views/seller/pages/tables/sales-invoice.php`
**Current state:** not linked from the sidebar (confirmed just now — grepped `include-sidebar.php` for any `invoice` reference, found none). Reachable at `seller/invoice/sales_invoice` and `seller/invoice?edit_id=X`.
**What changed since the original audit:** this session's P1 pass found `Invoice::get_sales_list()` was both unscoped (any seller could see every seller's orders with no parameters at all) and SQL-injectable via the date filter. Both fixed and verified live — a seller now only ever sees their own sales data, override attempts are ignored, and the date-filter injection is closed.
**Separately (P2, not yet fixed):** the Sales Invoice page's date-range filter and search box don't work at all — `sales_invoice_query_params`, the JS function the table needs, doesn't exist anywhere in the codebase. See `P2-BUGFIX-HANDOFF.md` item P2-8-adjacent (this specific one wasn't folded into the P2-8 batch since it's part of this larger "is this section even wanted" decision — if you keep Invoice, add it to the P2-8 batch fix).
**The actual decision:** relink this into the sidebar (it's now secure and mostly functional, just needs the search/filter JS fix), or remove the whole section if sellers were never meant to have a separate invoice view distinct from Orders.

### Decision-3 — The Customer list section
**Files:** `application/controllers/seller/Customer.php`, `application/views/seller/pages/tables/manage-customer.php`
**Current state:** not linked from the sidebar. Reachable at `seller/customer/`.
**What changed since the original audit:** P1 added the missing `customer_privacy` permission check to `view_customer()` (previously any seller could pull every customer's email/mobile/wallet balance regardless of that permission — now enforced, verified live against both a seller with the permission granted and one without). **Still broken as a P2 item:** the underlying query has a schema mismatch (`cities.id`/`cities.name` vs the real `city_id`/`city_name`) that makes it 500 regardless of permission — see `P2-BUGFIX-HANDOFF.md` item P2-3, which also flags that you must re-verify the permission check still holds once you fix that schema bug.
**The actual decision:** is "any permitted seller can browse the full customer list" an intended feature (looks like a deliberate permission-gated CRM-style view, given the dedicated `customer_privacy` toggle), or should it not exist at all? If keeping it: fix P2-3 and relink into the sidebar. If not: delete both files and the now-unused `customer_privacy` permission flag.

### Decision-4 — Pickup Location management
**Files:** `application/controllers/seller/Pickup_location.php`, `application/views/seller/pages/tables/manage-pickup_location.php`, `application/models/Pickup_location_model.php`
**Current state:** not linked from the sidebar. Reachable at `seller/pickup_location/manage_pickup_locations`.
**What changed since the original audit:** this session's P1 pass fixed two real vulnerabilities here — a seller could read another seller's full warehouse address/contact/coordinates by URL parameter, and could **overwrite and simultaneously steal ownership of** another seller's pickup location record. Both fixed and verified live (confirmed the attack now leaves the target row completely untouched, and the legitimate own-row update still works).
**Still broken as a P2 item:** search/sort/pagination don't work on this table (same `queryParams` root cause as P2-8 — already listed there as one of the 5 tables to fix together).
**The actual decision:** this feature (letting a seller register pickup addresses, presumably for Shiprocket) looks genuinely useful and likely intended — it's referenced by the Shiprocket order-creation flow in `Orders.php`. Probably just needs relinking into the sidebar plus the P2-8 fix, but confirm with the user rather than assuming.

### Decision-5 — Fund Transfer (already flagged in P2 as P2-13)
See `P2-BUGFIX-HANDOFF.md` item P2-13 for full detail — this one looks the least likely to be an intentional seller feature (it renders the *delivery-boy* template and queries delivery-boy endpoints, reads like an unfinished copy-paste). Repeating here only so it's not missed in a P3-only pass: **flag to the user before doing anything with `Fund_transfer.php` / `manage-fund-transfers.php`.**

### Decision-6 — Admin code sitting inside a seller controller
**File:** `application/controllers/seller/Payment_request.php` — `index()` (confirmed at line 18) and `update_payment_request()` (confirmed at line 48)
**Current state:** both methods gate on `$this->ion_auth->is_admin()` (not `is_seller()`) and `index()` renders `admin/template`, not `seller/template`. This is admin-only functionality, misplaced in the seller controller namespace — a seller hitting these URLs gets redirected away (since they're not an admin), so it's not a live vulnerability, just organizationally wrong.
**Action:** this isn't really a P3 delete candidate — it's a **move**, not a removal. Relocate `index()` and `update_payment_request()` into `application/controllers/admin/Payment_request.php` (check if that file already exists and already has equivalent methods before moving, to avoid duplicating), and leave only the genuinely seller-facing methods (`add_withdrawal_request`, `view_withdrawal_request_list` — already correctly seller-scoped and already hardened by P1's search-leak fix) in the seller controller.

### Decision-7 — `Attribute_set.php` and `Attribute_value.php` index pages
**Files:** `application/controllers/seller/Attribute_set.php`, `application/controllers/seller/Attribute_value.php`, `application/views/seller/pages/tables/manage-attribute-set.php`, `application/views/seller/pages/tables/manage-attribute-value.php`
**Current state:** the `index()` page on each is unlinked from the sidebar (confirmed just now), but the AJAX list methods (`attribute_set_list()`, `attribute_value_list()`) **are** actively used — `manage-attributes-hub.php` (which *is* linked, at `seller/attributes/manage_all`) calls them directly. So the backend is live and in use; only the standalone index pages for each are orphaned.
**Same P2 issue as the rest of this batch:** both index pages' tables have the same dead `queryParams` search/sort bug (P2-8 candidate — not currently in that list since they weren't confirmed as linked, but worth adding if you decide to keep them).
**The actual decision:** these two standalone pages are probably redundant with the combined hub page (`manage-attributes-hub.php`) that already shows attribute sets and values together — likely safe to delete the two standalone `index()` methods + their views, keeping only the `_list()` AJAX methods that the hub actually depends on. Confirm with the user rather than assuming, since it's plausible they were meant to be separate detail-drill-down pages.

---

## Also worth knowing: custom.js-dependent "dead" endpoints — now hardened, not deleted

The original audit flagged a long list of Product/Orders/Area/Home controller methods as "dead endpoint" because they're only ever called from `assets/admin/custom/custom.js`, which isn't loaded on seller pages (see `P2-BUGFIX-HANDOFF.md`'s P2-5/6/7/8 group for the full explanation of that root cause). Several of these — `Product::delete_rating`, `Product::get_rating_list`, `Product::delete_product`, `Orders::update_order_status`, `Orders::update_order_mail_status` and others — turned out during the P1 pass to be genuinely exploitable cross-seller (delete a competitor's reviews, cancel a competitor's order, etc.) despite being unreachable from the current seller UI. **All of those are now fixed at the code level** rather than deleted, since deleting a still-routable, now-secure endpoint is optional cleanup, not a security requirement.

If your actual intent is "seller pages should get `custom.js` back" (fixing the *root cause* rather than patching each symptom — this is explicitly floated as an option in `P2-BUGFIX-HANDOFF.md`'s P2-5 discussion), then all of this P1 hardening remains correct and necessary regardless — those endpoints would still need to not trust attacker-supplied ids even with the UI restored. Don't treat "the UI works now" as a reason to revert any of the ownership checks from P1.

---

## Suggested order

1. Do the two no-decision deletes (D-1, D-2) — trivial, no risk.
2. Take the 7 decision items to the user as a single batch of yes/no questions ("keep and relink" vs "remove") — they're independent of each other, so this can be one conversation rather than seven.
3. For whatever you're told to keep, fold the matching P2 items (mostly the `queryParams` search/sort fix) into that same work.
4. For whatever you're told to remove, delete the controller + model methods + view files together, and double-check nothing else in the codebase still references them (grep the controller/method name across the whole repo, not just the seller views, before deleting — some of these are also called from the mobile API controllers, e.g. `seller/app/v1/Api.php`, which may need to keep working independently of the web UI decision).
