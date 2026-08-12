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
|
| Suggested schedule: 0 2 * * *  (expiry sweep) and 0 8 * * *  (reminders), so
| reminders go out at a sensible hour after the sweep has run.
| ---------------------------------------------------------------------------
*/
$config['secret'] = getenv('SUBSCRIPTION_CRON_SECRET')
    ?: '842e7d305567c1453f321fdb30fcf50e813d0eaeeb8cc452dfed202c56239aeb';

/*
| How many days ahead of expiry a seller gets a renewal reminder. One email is
| sent per seller per threshold per subscription period - see the
| seller_subscription_reminders table - so re-running the cron is safe and will
| not spam anyone.
*/
$config['expiry_reminder_days'] = [7, 3, 1];
