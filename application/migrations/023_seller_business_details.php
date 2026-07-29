<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Seller Business Details step (formerly "Account Details" tab) overhaul.
 * Adds a legal business/firm name, a dedicated business address (distinct
 * from the personal address and the pickup address), and the document
 * uploads called for per entity type: PAN card, GSTIN, GST enrollment
 * acknowledgement, business proof, business address proof, partnership
 * deed, and bank account proof.
 */
class Migration_seller_business_details extends CI_Migration
{
    public function up()
    {
        $fields = array(
            'legal_business_name' => array(
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
            'business_address1' => array(
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
            'business_address2' => array(
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
            'business_pin' => array(
                'type'       => 'VARCHAR',
                'constraint' => '10',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
            'business_city' => array(
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
            'business_district' => array(
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
            'business_state' => array(
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
            'pan_card_document' => array(
                'type'       => 'VARCHAR',
                'constraint' => '512',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
            'gstin_document' => array(
                'type'       => 'VARCHAR',
                'constraint' => '512',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
            'gst_enrollment_ack_document' => array(
                'type'       => 'VARCHAR',
                'constraint' => '512',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
            'business_proof_document' => array(
                'type'       => 'VARCHAR',
                'constraint' => '512',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
            'business_address_proof_document' => array(
                'type'       => 'VARCHAR',
                'constraint' => '512',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
            'partnership_deed_document' => array(
                'type'       => 'VARCHAR',
                'constraint' => '512',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
            'bank_account_proof_document' => array(
                'type'       => 'VARCHAR',
                'constraint' => '512',
                'NULL'       => TRUE,
                'default'    => NULL,
            ),
        );
        $this->dbforge->add_column('seller_data', $fields);
    }

    public function down()
    {
        $columns = array(
            'legal_business_name', 'business_address1', 'business_address2', 'business_pin',
            'business_city', 'business_district', 'business_state', 'pan_card_document',
            'gstin_document', 'gst_enrollment_ack_document', 'business_proof_document',
            'business_address_proof_document', 'partnership_deed_document', 'bank_account_proof_document',
        );
        foreach ($columns as $column) {
            $this->dbforge->drop_column('seller_data', $column);
        }
    }
}
