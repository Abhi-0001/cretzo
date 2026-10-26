<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Identity proof: a second image for the back side.
 *
 * An Aadhaar card, driving licence or voter ID has a front and a back, and the profile form only
 * had room for one file. `national_identity_card_back` is that second file (optional; the front
 * stays the required one). It is held for admin approval like the front once a seller is approved.
 */
class Migration_seller_identity_proof_back extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('national_identity_card_back', 'seller_data')) {
            $this->dbforge->add_column('seller_data', [
                'national_identity_card_back' => ['type' => 'TEXT', 'null' => TRUE],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('national_identity_card_back', 'seller_data')) {
            $this->dbforge->drop_column('seller_data', 'national_identity_card_back');
        }
    }
}
