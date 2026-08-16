<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tracks which low-stock warnings have already been sent.
 *
 * Without this the alert sweep would email the same seller about the same product on every
 * run, which trains people to ignore it. A row is claimed before the mail goes out, so a
 * duplicate or overlapping run cannot double-send; the claim records the level it was raised
 * at, so a FURTHER drop counts as a new condition worth reporting, and the row is deleted
 * once the product recovers above the threshold, arming the alert again.
 */
class Migration_low_stock_alerts extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('low_stock_alerts')) {
            return;
        }

        $this->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => '11',
                'auto_increment' => TRUE,
                'NULL'           => FALSE,
            ],
            'product_id' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => FALSE,
            ],
            'product_variant_id' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => FALSE,
            ],
            // The stock level the warning was raised at, so a further drop can re-alert.
            'alerted_at_stock' => [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => FALSE,
                'default'    => 0,
            ],
            'created_at DATETIME NULL',
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('product_id');
        $this->dbforge->create_table('low_stock_alerts', TRUE);

        // One open warning per variant. This UNIQUE key is what makes claiming safe against
        // two overlapping cron runs: the second insert fails rather than double-sending.
        $existing = $this->db->query(
            "SHOW INDEX FROM `low_stock_alerts` WHERE Key_name = 'uniq_low_stock_variant'"
        )->num_rows();
        if ($existing == 0) {
            $this->db->query(
                'ALTER TABLE `low_stock_alerts` ADD UNIQUE KEY `uniq_low_stock_variant` (`product_variant_id`)'
            );
        }
    }

    public function down()
    {
        $this->dbforge->drop_table('low_stock_alerts', TRUE);
    }
}
