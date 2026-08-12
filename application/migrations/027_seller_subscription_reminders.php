<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tracks which renewal reminders have already been sent, so the expiry-reminder cron is
 * idempotent: re-running it (or running it more than once a day) can never email the same
 * seller about the same threshold on the same subscription period twice.
 *
 * The unique key is what actually enforces that - the cron relies on the INSERT failing
 * rather than on a SELECT-then-INSERT, which would race against a second cron run.
 */
class Migration_seller_subscription_reminders extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('seller_subscription_reminders')) {
            return;
        }

        $this->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => '11',
                'auto_increment' => TRUE,
                'NULL'           => FALSE,
            ],
            // The specific subscription period being warned about, NOT the seller - a
            // seller who renews starts a new row and so becomes eligible for reminders
            // again, which is exactly what should happen.
            'seller_subscription_id' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => FALSE,
            ],
            'seller_id' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => FALSE,
            ],
            // Days before expiry this reminder was for; 0 means the "expired today" notice.
            'threshold_days' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => FALSE,
            ],
            'sent_at' => [
                'type' => 'DATETIME',
                'NULL' => TRUE,
            ],
            'created_at TIMESTAMP default CURRENT_TIMESTAMP',
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('seller_id');
        $this->dbforge->create_table('seller_subscription_reminders', TRUE);

        $this->db->query('ALTER TABLE `seller_subscription_reminders`
            ADD UNIQUE KEY `uniq_reminder` (`seller_subscription_id`, `threshold_days`)');
    }

    public function down()
    {
        $this->dbforge->drop_table('seller_subscription_reminders', TRUE);
    }
}
