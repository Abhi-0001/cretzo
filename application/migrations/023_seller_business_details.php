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
                // Only add columns that aren't already present. add_column() emits a bare
        // ALTER TABLE ... ADD, which fails with "Duplicate column name" if this migration
        // is replayed on a database where the column already exists (e.g. a restored dump,
        // or a `migrations` version that lags the real schema). One failure aborts the
        // whole run, so every later migration is blocked too.
        $fields = array_filter($fields, function ($name) {
            return !$this->db->field_exists($name, 'seller_data');
        }, ARRAY_FILTER_USE_KEY);
        if (empty($fields)) {
            return;
        }

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
