<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Foundation for the referral program: attribution tables, the rule catalogue,
 * and a referral code for every account.
 *
 * PHASE 1 OF 5. This migration creates the ledger the later phases write into;
 * it moves no money and pays nobody. Every seeded program starts DISABLED
 * (`status` 0) so that when the reward engine lands in phase 2 it has nothing to
 * act on until the owner turns a program on deliberately, with the amounts they
 * have agreed to.
 *
 * WHAT WAS ALREADY HERE
 * ---------------------
 * The base platform shipped a customer-to-customer refer-and-earn: the settings
 * group (`is_refer_earn_on`, `refer_earn_bonus`, ...), `users`.`referral_code`
 * and `users`.`friends_code`, and process_referral_bonus() called from the nine
 * places an order can be marked delivered. It has never paid anybody, and could
 * not:
 *
 *   - web signup never generated a referral_code, so nobody had a code to share
 *     (confirmed against this database: 1 of 38 users has one, the seeded admin);
 *   - it credits through customer_model->update_balance(), the deprecated wallet
 *     path that moves users.balance outside a transaction;
 *   - its idempotency key is the ORDER id, and it gates on the referee's lifetime
 *     order count, so one buyer's first few orders each pay the referrer again.
 *
 * Those two `users` columns and the settings group are kept - the columns because
 * they are the natural place for the code, and the settings because removing them
 * is phase 2's business, once the engine that replaces process_referral_bonus()
 * exists. Nothing here changes their meaning.
 *
 * WHY FOUR TABLES AND NOT A COLUMN OR TWO
 * ---------------------------------------
 * A referral reward is not credited when it is earned. It is attributed at
 * signup, becomes pending when a milestone matches, qualified when the hold
 * window closes, and only then credited - and it must be reversible when the
 * order behind it is returned. That is a state machine with money attached, so
 * it needs its own rows.
 *
 * The single most important line in this file is the UNIQUE key on
 * `referral_rewards` (referral_id, milestone_id, role). The old feature's
 * double-payment bug becomes structurally impossible rather than merely guarded
 * against - which matters in this codebase, where money paths exist in two to
 * four near-duplicate copies (web and app x customer and seller) and a fix to one
 * copy routinely misses its twins. A unique index holds for all of them.
 *
 * IDEMPOTENCY
 * -----------
 * Everything here is written to survive being run twice: CREATE TABLE IF NOT
 * EXISTS, column existence checks, seeds guarded by a lookup on their natural
 * key, and a backfill that only touches rows with no code.
 */
class Migration_referral_program extends CI_Migration
{
    /**
     * Seeded rule set. Matches the published reward matrix, and every amount here
     * is editable from the admin panel afterwards - these are starting values, not
     * constants. `status` 0 on every program: seeding a rule is not switching a
     * program on.
     */
    private function programs()
    {
        return [
            [
                'code'          => 'customer_customer',
                'name'          => 'Customer refers a customer',
                'referrer_role' => 'customer',
                'referee_role'  => 'customer',
                'milestones'    => [
                    [
                        'code'                  => 'first_delivered_order',
                        'name'                  => 'Referred customer\'s first delivered order',
                        'referrer_amount'       => 100,
                        'referee_benefit_type'  => 'promo_code',
                        'referee_benefit_value' => 100,
                        'min_order_amount'      => 500,
                        'sequence'              => 1,
                    ],
                ],
            ],
            [
                'code'          => 'seller_seller',
                'name'          => 'Seller refers a seller',
                'referrer_role' => 'seller',
                'referee_role'  => 'seller',
                'milestones'    => [
                    [
                        'code'                  => 'kyc_shop_live',
                        'name'                  => 'Referred seller approved and shop live',
                        'referrer_amount'       => 100,
                        'referee_benefit_type'  => 'listing_bonus',
                        'referee_benefit_value' => 25,
                        'min_order_amount'      => 0,
                        'sequence'              => 1,
                    ],
                    [
                        'code'                  => 'first_delivered_order',
                        'name'                  => 'Referred seller\'s first delivered order',
                        'referrer_amount'       => 100,
                        'referee_benefit_type'  => 'none',
                        'referee_benefit_value' => 0,
                        'min_order_amount'      => 0,
                        'sequence'              => 2,
                    ],
                ],
            ],
            [
                'code'          => 'seller_customer',
                'name'          => 'Seller refers a customer',
                'referrer_role' => 'seller',
                'referee_role'  => 'customer',
                'milestones'    => [
                    [
                        'code'                  => 'first_delivered_order',
                        'name'                  => 'Referred customer\'s first delivered order',
                        'referrer_amount'       => 100,
                        'referee_benefit_type'  => 'promo_code',
                        'referee_benefit_value' => 100,
                        'min_order_amount'      => 500,
                        'sequence'              => 1,
                    ],
                ],
            ],
            [
                /* The ambassador tiers are counted across every program, so they are
                 * one program whose milestones are reached by referral COUNT rather
                 * than by an event on a single referral. */
                'code'          => 'ambassador',
                'name'          => 'Ambassador tiers',
                'referrer_role' => 'any',
                'referee_role'  => 'any',
                'milestones'    => [
                    ['code' => 'tier_5',  'name' => 'Starter - 5 successful referrals',   'referrer_amount' => 500,  'referee_benefit_type' => 'none', 'referee_benefit_value' => 0, 'min_order_amount' => 0, 'sequence' => 1],
                    ['code' => 'tier_10', 'name' => 'Champion - 10 successful referrals', 'referrer_amount' => 1000, 'referee_benefit_type' => 'none', 'referee_benefit_value' => 0, 'min_order_amount' => 0, 'sequence' => 2],
                    ['code' => 'tier_25', 'name' => 'Elite - 25 successful referrals',    'referrer_amount' => 2500, 'referee_benefit_type' => 'none', 'referee_benefit_value' => 0, 'min_order_amount' => 0, 'sequence' => 3],
                ],
            ],
        ];
    }

    public function up()
    {
        $this->create_tables();
        $this->add_user_columns();
        $this->seed_programs();
        $this->backfill_referral_codes();
    }

    private function create_tables()
    {
        /* One row per line of the reward matrix. budget_cap is the program-level
         * ceiling: an ambassador who brings 25 sellers commits Rs 9,000 of wallet
         * liability on their own, so the cap pauses a program rather than letting it
         * overspend quietly. NULL means no cap. */
        $this->db->query("CREATE TABLE IF NOT EXISTS `referral_programs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `code` VARCHAR(32) NOT NULL,
            `name` VARCHAR(128) NOT NULL,
            `referrer_role` VARCHAR(16) NOT NULL DEFAULT 'customer',
            `referee_role` VARCHAR(16) NOT NULL DEFAULT 'customer',
            `status` TINYINT(1) NOT NULL DEFAULT 0,
            `budget_cap` DECIMAL(12,2) NULL DEFAULT NULL,
            `spent_to_date` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `per_referrer_monthly_cap` DECIMAL(12,2) NULL DEFAULT NULL,
            `starts_at` DATETIME NULL DEFAULT NULL,
            `ends_at` DATETIME NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_referral_program_code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        /* What triggers a payout and how much. hold_days is the gap between a
         * milestone matching and the money being creditable - it exists so a
         * returned order cannot outlive its own reward. */
        $this->db->query("CREATE TABLE IF NOT EXISTS `referral_milestones` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `program_id` INT(11) NOT NULL,
            `code` VARCHAR(32) NOT NULL,
            `name` VARCHAR(128) NOT NULL,
            `referrer_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `referee_benefit_type` VARCHAR(16) NOT NULL DEFAULT 'none',
            `referee_benefit_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `min_order_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `hold_days` INT(11) NOT NULL DEFAULT 7,
            `sequence` INT(11) NOT NULL DEFAULT 1,
            `status` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_referral_milestone` (`program_id`, `code`),
            CONSTRAINT `fk_referral_milestone_program` FOREIGN KEY (`program_id`)
                REFERENCES `referral_programs` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        /* The attribution record, written once at signup and never edited: a user
         * who could re-enter a code later would farm codes by editing their profile.
         * Hence UNIQUE (referee_id) - referred exactly once, ever.
         *
         * signup_ip and signup_device_id are fraud SIGNALS, not rejections. Two
         * genuine referrals often happen on one phone in one shop, so a shared
         * signal raises `flagged` for a human to look at and nothing more. */
        $this->db->query("CREATE TABLE IF NOT EXISTS `referrals` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `referrer_id` INT(11) NOT NULL,
            `referee_id` INT(11) NOT NULL,
            `program_id` INT(11) NULL DEFAULT NULL,
            `code_used` VARCHAR(32) NOT NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT 'attributed',
            `flagged` TINYINT(1) NOT NULL DEFAULT 0,
            `flag_reason` VARCHAR(255) NULL DEFAULT NULL,
            `signup_ip` VARCHAR(45) NULL DEFAULT NULL,
            `signup_device_id` VARCHAR(191) NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `completed_at` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_referral_referee` (`referee_id`),
            KEY `idx_referral_referrer` (`referrer_id`),
            KEY `idx_referral_program` (`program_id`),
            KEY `idx_referral_flagged` (`flagged`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        /* The money. `transaction_id` is filled in when the wallet is credited, so
         * a reward row can always be traced to the ledger line that paid it, and
         * `source_order_id` is what a return watches to reverse the right reward. */
        $this->db->query("CREATE TABLE IF NOT EXISTS `referral_rewards` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `referral_id` INT(11) NOT NULL,
            `milestone_id` INT(11) NOT NULL,
            `beneficiary_id` INT(11) NOT NULL,
            `role` VARCHAR(16) NOT NULL DEFAULT 'referrer',
            `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `benefit_type` VARCHAR(16) NOT NULL DEFAULT 'wallet',
            `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
            `transaction_id` INT(11) NULL DEFAULT NULL,
            `source_order_id` INT(11) NULL DEFAULT NULL,
            `flagged` TINYINT(1) NOT NULL DEFAULT 0,
            `flag_reason` VARCHAR(255) NULL DEFAULT NULL,
            `note` VARCHAR(255) NULL DEFAULT NULL,
            `qualified_at` DATETIME NULL DEFAULT NULL,
            `credited_at` DATETIME NULL DEFAULT NULL,
            `reviewed_by` INT(11) NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_referral_reward` (`referral_id`, `milestone_id`, `role`),
            KEY `idx_reward_beneficiary` (`beneficiary_id`, `status`),
            KEY `idx_reward_status` (`status`, `qualified_at`),
            KEY `idx_reward_source_order` (`source_order_id`),
            CONSTRAINT `fk_referral_reward_referral` FOREIGN KEY (`referral_id`)
                REFERENCES `referrals` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /**
     * `listing_bonus` is the deliberate choice over the tempting alternative of
     * bumping a rewarded seller's subscription plan: plan rows are shared catalogue
     * records (migration 074), so editing one to reward one seller raises the cap
     * for everyone on that plan. A per-user additive bonus keeps the catalogue
     * clean and is read wherever the cap is enforced.
     */
    private function add_user_columns()
    {
        $columns = [
            'listing_bonus'   => "ALTER TABLE `users` ADD COLUMN `listing_bonus` INT(11) NOT NULL DEFAULT 0 AFTER `friends_code`",
            'ambassador_tier' => "ALTER TABLE `users` ADD COLUMN `ambassador_tier` TINYINT(4) NOT NULL DEFAULT 0 AFTER `listing_bonus`",
        ];

        foreach ($columns as $column => $sql) {
            if (!$this->db->field_exists($column, 'users')) {
                $this->db->query($sql);
            }
        }
    }

    private function seed_programs()
    {
        foreach ($this->programs() as $program) {
            $milestones = $program['milestones'];
            unset($program['milestones']);

            $existing = $this->db->select('id')->where('code', $program['code'])->get('referral_programs')->row_array();

            if (empty($existing)) {
                $this->db->insert('referral_programs', $program);
                $program_id = $this->db->insert_id();
            } else {
                /* Re-running must not resurrect a program the owner has since edited
                 * or switched off - only the id is taken from an existing row. */
                $program_id = $existing['id'];
            }

            if (empty($program_id)) {
                continue;
            }

            foreach ($milestones as $milestone) {
                $milestone['program_id'] = $program_id;

                $has = $this->db->where(['program_id' => $program_id, 'code' => $milestone['code']])
                    ->count_all_results('referral_milestones');

                if (!$has) {
                    $this->db->insert('referral_milestones', $milestone);
                }
            }
        }
    }

    /**
     * Every existing account gets a code, so the program has something to share
     * from day one and so the UNIQUE index below can be added at all.
     *
     * The codes are generated in PHP rather than by an expression like
     * UPPER(SUBSTR(MD5(id))): a code derived from the row id is guessable, and the
     * whole point of the code is that only its owner can hand it out.
     */
    private function backfill_referral_codes()
    {
        $users = $this->db->select('id')
            ->group_start()->where('referral_code IS NULL', null, false)->or_where('referral_code', '')->group_end()
            ->get('users')->result_array();

        $taken = [];
        foreach ($this->db->select('referral_code')->where('referral_code IS NOT NULL', null, false)->get('users')->result_array() as $row) {
            if ($row['referral_code'] !== '') {
                $taken[strtoupper($row['referral_code'])] = true;
            }
        }

        foreach ($users as $user) {
            /* Ambiguous glyphs (0/O, 1/I) are excluded: these codes get read aloud
             * and typed from screenshots. */
            $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $code = '';

            for ($attempt = 0; $attempt < 20; $attempt++) {
                $candidate = '';
                for ($i = 0; $i < 8; $i++) {
                    $candidate .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                }
                if (!isset($taken[$candidate])) {
                    $code = $candidate;
                    break;
                }
            }

            if ($code === '') {
                continue;
            }

            $taken[$code] = true;
            $this->db->where('id', $user['id'])->update('users', ['referral_code' => $code]);
        }

        /* Only now can this be UNIQUE - before the backfill, every code-less row
         * held the same NULL/'' value. MySQL permits any number of NULLs in a unique
         * index, so a row that somehow escapes the backfill still inserts.
         *
         * The index is what makes the code lookup at signup a key read instead of a
         * table scan, and it is the last line of defence against two users sharing a
         * code, which would silently misattribute every referral one of them makes. */
        $indexes = $this->db->query("SHOW INDEX FROM `users` WHERE Key_name = 'uq_users_referral_code'")->result_array();
        if (empty($indexes)) {
            $duplicates = $this->db->query("SELECT referral_code FROM `users`
                WHERE referral_code IS NOT NULL AND referral_code <> ''
                GROUP BY referral_code HAVING COUNT(*) > 1")->result_array();

            /* A duplicate here means pre-existing data this migration must not
             * destroy by picking a winner. Leave the index off and let phase 2's
             * admin screens surface it. */
            if (empty($duplicates)) {
                $this->db->query("ALTER TABLE `users` ADD UNIQUE KEY `uq_users_referral_code` (`referral_code`)");
            } else {
                log_message('error', 'Migration 078: duplicate referral_code values present; unique index not added.');
            }
        }
    }

    public function down()
    {
        /* The tables are dropped children-first; the users columns and the codes are
         * left alone. Codes are handed out to real people the moment this ships, and
         * dropping the column would break every share link already in circulation
         * for the sake of a rollback that is only ever run in development. */
        $this->db->query("DROP TABLE IF EXISTS `referral_rewards`");
        $this->db->query("DROP TABLE IF EXISTS `referral_milestones`");
        $this->db->query("DROP TABLE IF EXISTS `referrals`");
        $this->db->query("DROP TABLE IF EXISTS `referral_programs`");
    }
}
