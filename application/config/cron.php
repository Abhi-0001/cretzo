<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| Shared secret for token-protected cron endpoints that run from an external
| OS-level scheduler (Hostinger cron, etc.) without a logged-in admin session.
| Pass it as ?token=... in the cron URL.
|
| Endpoints protected by this secret:
|   admin/cron_job/expire_seller_subscriptions   - flips is_active off on lapsed rows
|   admin/cron_job/subscription_expiry_reminders - emails sellers before/on expiry
|   admin/cron_job/expire_abandoned_orders       - releases stock held by unpaid orders
|   admin/cron_job/low_stock_alerts              - emails sellers about low stock
|
| The env var lets production override this without the value living in git.
| Rotate by changing SUBSCRIPTION_CRON_SECRET (or this literal) and updating the
| cron URLs to match.
|
| ---------------------------------------------------------------------------
| Hostinger cron setup (hPanel -> Advanced -> Cron Jobs), both once daily:
|
|   curl -s "https://YOURDOMAIN/admin/cron_job/expire_seller_subscriptions?token=THE_SECRET"
|   curl -s "https://YOURDOMAIN/admin/cron_job/subscription_expiry_reminders?token=THE_SECRET"
|   curl -s "https://YOURDOMAIN/admin/cron_job/expire_abandoned_orders?token=THE_SECRET"
|   curl -s "https://YOURDOMAIN/admin/cron_job/low_stock_alerts?token=THE_SECRET"
|
| Suggested schedule:
|   0 2 * * *   expire_seller_subscriptions   (expiry sweep)
|   0 8 * * *   subscription_expiry_reminders (reminders, after the sweep)
|   0 * * * *   expire_abandoned_orders       (hourly - the sooner unpaid stock is
|                                              released, the sooner it can sell)
|   0 9 * * *   low_stock_alerts              (once daily, at a sensible hour)
| ---------------------------------------------------------------------------
*/
/*
| This file is TRACKED IN GIT, so a secret written here is a secret published to
| everyone with repository access - and these endpoints move real money
| (settle_seller_commission credits seller wallets, settle_cashback_discount pays
| out promo cashback). The previous hard-coded fallback was exactly that, so it is
| gone and MUST be replaced with the environment variable.
|
| Set it on the server, e.g. in the vhost / .htaccess / panel environment:
|     SetEnv SUBSCRIPTION_CRON_SECRET "<a long random string>"
| or generate one with:  php -r "echo bin2hex(random_bytes(32));"
|
| Leaving it unset is FAIL-CLOSED, not fail-open: cron_authorized() treats an empty
| secret as "refuse everything", so the token-protected endpoints answer
| Unauthorized until the variable is present. A logged-in admin can still trigger
| them from the panel in the meantime, so nothing is stuck - but the nightly jobs
| will not run, which is the loud failure you want rather than a silent one.
*/
$config['secret'] = getenv('SUBSCRIPTION_CRON_SECRET') ?: '';

/*
| How many days ahead of expiry a seller gets a renewal reminder. One email is
| sent per seller per threshold per subscription period - see the
| seller_subscription_reminders table - so re-running the cron is safe and will
| not spam anyone.
*/
$config['expiry_reminder_days'] = [7, 3, 1];

/*
| How long an order may sit unpaid before expire_abandoned_orders cancels it and
| returns its stock.
|
| Stock is taken when the order is CREATED, before payment. Bank transfer and the
| app's online-payment flow leave an order at 'awaiting' - if the customer never
| completes payment, that stock was held indefinitely with no sale behind it and
| nothing to release it. Only 'awaiting' items are ever touched: every path that
| confirms a payment moves the item to 'received' first.
|
| Set this longer than your slowest legitimate payment method. Bank transfers in
| particular can take a day or two to be confirmed by an admin.
*/
$config['abandoned_order_hours'] = 48;
