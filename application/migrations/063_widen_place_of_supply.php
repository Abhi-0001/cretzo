<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Widen seller_settlements.place_of_supply.
 *
 * It was created at VARCHAR(10) in migration 062, one character short of the value it
 * actually stores: 'intra_state' is 11 characters and MySQL silently truncated it to
 * 'intra_stat'. Truncated silently is the problem - the column reads back as a value that
 * matches nothing, so an intra-state settlement looked unclassified in every report that
 * compares against it. Migration 062 has been corrected for fresh installs; this repairs
 * any database that already ran it, and rewrites the rows that were truncated.
 */
class Migration_widen_place_of_supply extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('place_of_supply', 'seller_settlements')) {
            return;
        }

        $this->dbforge->modify_column('seller_settlements', [
            'place_of_supply' => [
                'name'       => 'place_of_supply',
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'NULL'       => TRUE,
            ],
        ]);

        $this->db->where('place_of_supply', 'intra_stat')
            ->update('seller_settlements', ['place_of_supply' => 'intra_state']);
    }

    public function down()
    {
        // Deliberately empty: narrowing the column again would re-truncate the data.
    }
}
