<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * =============================================================================
 *  REFERRAL PROGRAM - ATTRIBUTION (PHASE 1)
 * =============================================================================
 *
 * Codes, and the binding of one user to another at signup. NOTHING in this file
 * moves money: it writes `referrals` rows and captures fraud signals. The reward
 * engine (milestones, wallet credits, reversals) is phase 2 and lives elsewhere;
 * this layer is what it will read.
 *
 * WHY THIS IS A HELPER AND NOT CODE IN THE CONTROLLERS
 * ---------------------------------------------------
 * Registration happens in fifteen places in this codebase - web customer, app
 * customer, three seller paths, social login, delivery boy, POS walk-in, admin
 * creating a seller. Money and identity logic in this project is known to exist
 * in two-to-four near-duplicate copies, and a fix applied to one copy routinely
 * misses its twins. So the two things that must happen for EVERY new account -
 * get a code, honour a code that was entered - are hooked once, inside
 * Ion_auth_model::register(), which every one of those paths already calls, and
 * the logic they call lives here.
 *
 * WHAT A BINDING IS
 * -----------------
 * Immutable. Written at signup, never editable afterwards: a user who could
 * enter a code later - or change one - would farm codes by editing their profile
 * repeatedly. `referrals` has UNIQUE (referee_id) so the database enforces
 * "referred exactly once, ever" even if a caller tries twice.
 *
 * FRAUD SIGNALS ARE NOT REJECTIONS
 * --------------------------------
 * Only the hard cases are refused here: referring yourself, using a code that
 * does not exist, and a second binding. A shared signup IP or device is FLAGGED
 * and nothing more - genuine referrals routinely happen on one phone in one
 * shop, and auto-rejecting those would quietly punish the honest majority to
 * stop a fraud that phase 3's review queue can catch with a human looking at it.
 */

/**
 * Alphabet for generated codes. 0/O and 1/I are excluded on purpose: these codes
 * get read aloud, written on packaging inserts and typed from screenshots.
 */
define('REFERRAL_CODE_ALPHABET', 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789');
define('REFERRAL_CODE_LENGTH', 8);

/**
 * Normalised form of anything a user typed. Codes are stored and compared in
 * upper case, so "  hj4k-cd2p " and "HJ4KCD2P" are the same code; the separator
 * strip exists because share links get copied with formatting around them.
 */
function referral_normalize_code($code)
{
    $code = strtoupper(trim((string) $code));
    $code = preg_replace('/[^A-Z0-9]/', '', $code);

    return (string) $code;
}

/**
 * A code that no user holds yet. Random, not derived from the row id - a code
 * anyone can compute from a user id is not a secret, and handing it out is the
 * entire mechanism.
 *
 * Returns '' if 20 attempts all collided, which at 32^8 possibilities means
 * something is wrong with the database rather than with luck; callers treat ''
 * as "no code this time" and carry on, because a missing code must never fail a
 * registration.
 */
function referral_generate_code()
{
    $t = &get_instance();
    $length = strlen(REFERRAL_CODE_ALPHABET) - 1;

    for ($attempt = 0; $attempt < 20; $attempt++) {
        $candidate = '';
        for ($i = 0; $i < REFERRAL_CODE_LENGTH; $i++) {
            $candidate .= REFERRAL_CODE_ALPHABET[random_int(0, $length)];
        }

        $exists = $t->db->where('referral_code', $candidate)->count_all_results('users');
        if (!$exists) {
            return $candidate;
        }
    }

    log_message('error', 'referral_generate_code: could not find a free code in 20 attempts');

    return '';
}

/**
 * The user's own code, generating and storing one if the account has none.
 *
 * Safe to call on every login or page render: an account that already has a code
 * costs one indexed read and keeps the code it has. Codes are permanent - a
 * regenerated code would break every share link the user has already sent.
 */
function referral_assign_code($user_id)
{
    $t = &get_instance();
    $user_id = (int) $user_id;

    if ($user_id <= 0) {
        return '';
    }

    $user = $t->db->select('referral_code')->where('id', $user_id)->get('users')->row_array();
    if (empty($user)) {
        return '';
    }

    if (!empty($user['referral_code'])) {
        return $user['referral_code'];
    }

    $code = referral_generate_code();
    if ($code === '') {
        return '';
    }

    /* Not update_details(): that runs the values through escape_array(), which in
     * this codebase compounds backslashes on repeated edits. A referral code is
     * pure A-Z0-9 so it would survive, but the query builder escapes correctly
     * and there is no reason to route new code through the known-bad path. */
    $t->db->where('id', $user_id)->update('users', ['referral_code' => $code]);

    return $code;
}

/** The account a code belongs to, or [] when the code is not in use. */
function referral_user_by_code($code)
{
    $t = &get_instance();
    $code = referral_normalize_code($code);

    if ($code === '') {
        return [];
    }

    $user = $t->db->select('id, username, email, mobile, referral_code')
        ->where('referral_code', $code)
        ->limit(1)
        ->get('users')
        ->row_array();

    return !empty($user) ? $user : [];
}

/** 'seller' when the account can sell, otherwise 'customer'. */
function referral_role_of($user_id)
{
    return user_has_role($user_id, 'seller') ? 'seller' : 'customer';
}

/**
 * The program a (referrer, referee) pair falls under, by role - seller->seller,
 * seller->customer, customer->customer. Returns [] when no program matches or
 * the matching one is switched off.
 *
 * A missing program does NOT stop the binding. Attribution is recorded either
 * way, so that switching a program on later can pay out on relationships formed
 * while it was off, and so the admin ledger shows who invited whom even for
 * pairs no program covers.
 */
function referral_resolve_program($referrer_id, $referee_id)
{
    $t = &get_instance();

    /* Both roles are resolved BEFORE the query builder chain starts.
     * referral_role_of() runs a query of its own, and CodeIgniter's query builder
     * is a single shared accumulator: calling it midway through ->where()...->get()
     * merges this function's conditions into that inner query and produces
     * "Unknown column 'referrer_role' in users_groups". */
    $referrer_role = referral_role_of($referrer_id);
    $referee_role  = referral_role_of($referee_id);

    $program = $t->db->select('id, code, status')
        ->where('referrer_role', $referrer_role)
        ->where('referee_role', $referee_role)
        ->where('code !=', 'ambassador')
        ->limit(1)
        ->get('referral_programs')
        ->row_array();

    return !empty($program) ? $program : [];
}

/**
 * Signup context worth keeping for later fraud review: the caller's IP and,
 * where the client sends one, its device identifier. The mobile apps post a
 * device id already (the app login flow uses it as part of its identity), and
 * the storefront has none - a NULL here is normal, not a red flag.
 */
function referral_signup_meta()
{
    $t = &get_instance();

    $device = '';
    foreach (['device_id', 'device_token', 'fcm_id'] as $key) {
        $posted = $t->input->post($key);
        if (!empty($posted)) {
            $device = substr((string) $posted, 0, 191);
            break;
        }
    }

    return [
        'signup_ip'        => $t->input->ip_address(),
        'signup_device_id' => ($device !== '') ? $device : null,
    ];
}

/**
 * Reasons this pair looks like one person rewarding themselves. Anything listed
 * here is a HARD refusal - the same account, or two accounts sharing a contact
 * detail, is not a referral under any reading.
 */
function referral_self_dealing_reasons($referrer, $referee)
{
    $reasons = [];

    if ((int) $referrer['id'] === (int) $referee['id']) {
        $reasons[] = 'same account';
        return $reasons;
    }

    foreach (['email', 'mobile'] as $field) {
        $a = isset($referrer[$field]) ? strtolower(trim((string) $referrer[$field])) : '';
        $b = isset($referee[$field]) ? strtolower(trim((string) $referee[$field])) : '';

        if ($a !== '' && $a === $b) {
            $reasons[] = 'shared ' . $field;
        }
    }

    return $reasons;
}

/**
 * Soft signals - recorded on the row, never acted on automatically. The
 * threshold is deliberately loose: a family or a shop counter genuinely shares
 * an IP, so this only means "worth a look" once the same referrer has collected
 * several signups from one place.
 */
function referral_soft_flags($referrer_id, $meta)
{
    $t = &get_instance();
    $flags = [];

    if (!empty($meta['signup_ip'])) {
        $same_ip = $t->db->where('referrer_id', $referrer_id)
            ->where('signup_ip', $meta['signup_ip'])
            ->count_all_results('referrals');

        if ($same_ip >= 2) {
            $flags[] = ($same_ip + 1) . ' signups from one IP';
        }
    }

    if (!empty($meta['signup_device_id'])) {
        $same_device = $t->db->where('signup_device_id', $meta['signup_device_id'])
            ->count_all_results('referrals');

        if ($same_device >= 1) {
            $flags[] = ($same_device + 1) . ' signups from one device';
        }
    }

    return $flags;
}

/**
 * Bind a new account to whoever referred it.
 *
 * Returns ['bound' => bool, 'reason' => <machine-readable>, 'referral_id' => int].
 * It NEVER throws and never returns something a caller should surface as a
 * registration failure: a bad referral code must not cost somebody their
 * account. The reason codes exist for logging and for the signup form's own
 * live validation, which checks the code BEFORE the account is created.
 *
 * @param int    $referee_id  the account just created
 * @param string $raw_code    whatever the user typed or the share link carried
 * @param array  $meta        optional override of referral_signup_meta()
 */
function referral_bind($referee_id, $raw_code, $meta = [], $source = null)
{
    $t = &get_instance();
    $referee_id = (int) $referee_id;
    $code = referral_normalize_code($raw_code);
    $result = ['bound' => false, 'reason' => '', 'referral_id' => 0];

    /* The channel the code travelled on. Taken from the request when the caller
     * does not pass one, so the fifteen registration paths do not each have to
     * remember to forward it. */
    if ($source === null) {
        $source = $t->input->post('referral_source', true);
    }
    $source = referral_normalize_source($source);

    if ($referee_id <= 0 || $code === '') {
        $result['reason'] = 'no_code';
        return $result;
    }

    /* Guarded here as well as by UNIQUE (referee_id): a duplicate insert would be
     * caught by the database, but as a query error in the log rather than as the
     * ordinary "this user was already referred" it actually is. */
    $already = $t->db->where('referee_id', $referee_id)->count_all_results('referrals');
    if ($already) {
        $result['reason'] = 'already_referred';
        return $result;
    }

    $referrer = referral_user_by_code($code);
    if (empty($referrer)) {
        $result['reason'] = 'unknown_code';
        return $result;
    }

    $referee = $t->db->select('id, username, email, mobile')->where('id', $referee_id)->get('users')->row_array();
    if (empty($referee)) {
        $result['reason'] = 'unknown_referee';
        return $result;
    }

    $self_dealing = referral_self_dealing_reasons($referrer, $referee);
    if (!empty($self_dealing)) {
        log_message('debug', 'referral_bind: refused self-referral (' . implode(', ', $self_dealing) . ')');
        $result['reason'] = 'self_referral';
        return $result;
    }

    if (empty($meta)) {
        $meta = referral_signup_meta();
    }

    $program = referral_resolve_program($referrer['id'], $referee_id);
    $flags   = referral_soft_flags($referrer['id'], $meta);

    $row = [
        'referrer_id'      => (int) $referrer['id'],
        'referee_id'       => $referee_id,
        'program_id'       => !empty($program) ? (int) $program['id'] : null,
        'code_used'        => $code,
        'signup_source'    => $source,
        'status'           => 'attributed',
        'flagged'          => !empty($flags) ? 1 : 0,
        'flag_reason'      => !empty($flags) ? substr(implode('; ', $flags), 0, 255) : null,
        'signup_ip'        => $meta['signup_ip'],
        'signup_device_id' => $meta['signup_device_id'],
    ];

    $t->db->insert('referrals', $row);
    $referral_id = (int) $t->db->insert_id();

    /* insert_id() returns the LAST id on this connection, so it lies after a
     * failed insert - a trap this codebase has been bitten by before (phantom
     * orders). affected_rows() is what actually says whether a row was written. */
    if ($t->db->affected_rows() < 1) {
        log_message('error', 'referral_bind: insert failed for referee ' . $referee_id);
        $result['reason'] = 'insert_failed';
        return $result;
    }

    $result['bound'] = true;
    $result['reason'] = 'attributed';
    $result['referral_id'] = $referral_id;

    return $result;
}

/**
 * What the signup form says about a code as it is typed, BEFORE an account
 * exists. Self-referral cannot be checked at this point - there is no account
 * yet to compare against - so this only answers "is this a real code".
 */
function referral_validate_code($raw_code)
{
    $code = referral_normalize_code($raw_code);

    if ($code === '') {
        return ['valid' => false, 'message' => 'Enter a referral code.'];
    }

    $referrer = referral_user_by_code($code);
    if (empty($referrer)) {
        return ['valid' => false, 'message' => 'That referral code does not exist.'];
    }

    $name = trim((string) $referrer['username']);

    return [
        'valid'   => true,
        'message' => ($name !== '') ? 'Referred by ' . $name : 'Referral code applied.',
        'code'    => $code,
    ];
}

/**
 * Share link for a code: the storefront home page with ?ref= on it.
 *
 * The home page, not a dedicated landing page, on purpose - somebody who scans a
 * card at a market stall should land in the shop, not on a sign-up wall. The
 * code is captured by the browser and waits there until they register.
 *
 * $source marks the channel so the ledger can tell a scanned card from a
 * forwarded message. It is only ever set for links we generate; a code typed by
 * hand carries nothing.
 */
function referral_share_link($code, $source = '')
{
    $code = referral_normalize_code($code);

    if ($code === '') {
        return base_url();
    }

    $query = 'ref=' . $code;
    if ($source !== '') {
        $query .= '&src=' . rawurlencode($source);
    }

    return base_url('?' . $query);
}

/**
 * The same link, marked as having come from a scanned QR code - and normalised
 * to the canonical public form.
 *
 * base_url() reflects however the person happened to reach the panel, which is
 * fine for a link they will click in the next thirty seconds and wrong for a QR
 * code: a card printed from a session on www.cretzo.com carries "www" into every
 * parcel for years, and one printed over plain http bakes in a scheme the site
 * should not still be answering on by then.
 *
 * So for QR codes only: drop a leading "www.", and upgrade http to https. Local
 * hostnames are left exactly as they are - forcing https on localhost would
 * produce a code that cannot be scanned during development, which is the one
 * place these are tested.
 */
function referral_qr_link($code)
{
    $link = referral_share_link($code, 'qr');

    $host = parse_url($link, PHP_URL_HOST);
    if ($host === false || $host === null) {
        return $link;
    }

    $is_local = in_array($host, ['localhost', '127.0.0.1'], true)
        || substr($host, 0, 4) === '192.'
        || substr($host, -6) === '.local';

    if ($is_local) {
        return $link;
    }

    if (strpos($host, 'www.') === 0) {
        $link = str_replace('://' . $host, '://' . substr($host, 4), $link);
    }

    return preg_replace('#^http://#', 'https://', $link);
}

/**
 * Normalised channel name for a referral, from whatever the signup form posted.
 *
 * Anything unrecognised becomes 'link' rather than being stored raw: this value
 * is written by a public form, and an unbounded string from a public form is how
 * a reporting column turns into an injection surface and a mess of one-off
 * values nobody can group by.
 */
function referral_normalize_source($source)
{
    $source = strtolower(trim((string) $source));

    return in_array($source, ['qr', 'link', 'typed'], true) ? $source : 'link';
}

/**
 * Counts for a user's own Refer & Earn screen. Money columns are read from
 * `referral_rewards`, which phase 1 never writes - they are zero until the
 * reward engine ships, and are here so the surfaces built on top do not need
 * changing when it does.
 */
function referral_stats($user_id)
{
    $t = &get_instance();
    $user_id = (int) $user_id;

    $stats = [
        'code'            => '',
        'share_link'      => '',
        'qr_link'         => '',
        'total'           => 0,
        'pending_rewards' => 0.0,
        'earned'          => 0.0,
    ];

    if ($user_id <= 0) {
        return $stats;
    }

    $stats['code']       = referral_assign_code($user_id);
    $stats['share_link'] = referral_share_link($stats['code']);
    $stats['qr_link']    = referral_qr_link($stats['code']);
    $stats['total']      = (int) $t->db->where('referrer_id', $user_id)->count_all_results('referrals');

    $money = $t->db->select("
            COALESCE(SUM(CASE WHEN status IN ('pending','qualified') THEN amount ELSE 0 END), 0) AS pending_rewards,
            COALESCE(SUM(CASE WHEN status = 'credited' THEN amount ELSE 0 END), 0) AS earned", false)
        ->where('beneficiary_id', $user_id)
        ->get('referral_rewards')
        ->row_array();

    if (!empty($money)) {
        $stats['pending_rewards'] = (float) $money['pending_rewards'];
        $stats['earned'] = (float) $money['earned'];
    }

    return $stats;
}
