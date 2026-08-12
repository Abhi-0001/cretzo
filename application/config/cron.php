<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| Shared secret for token-protected cron endpoints (e.g.
| admin/Cron_job::expire_seller_subscriptions) that need to run from an
| external OS-level scheduler (Hostinger cron, etc.) without a logged-in
| admin session. Change this to a long random value before wiring up any
| cron job, and pass it as ?token=... in the cron URL.
*/
$config['secret'] = 'change-me-before-use';
