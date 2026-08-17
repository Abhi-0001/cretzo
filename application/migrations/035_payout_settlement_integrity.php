<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_payout_settlement_integrity extends CI_Migration
{
    public function up()
    {
        // --- payment_requests ------------------------------------------------------------
        $fields = $this->db->field_data('payment_requests');
        $existing = [];
        foreach ($fields as $field) {
            $existing[$field->name] = $field;
        }

        if (isset($existing['amount_requested']) && strtolower($existing['amount_requested']->type) != 'decimal') {
            $this->dbforge->modify_column('payment_requests', [
                'amount_requested' => [
                    'name'       => 'amount_requested',
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'NULL'       => FALSE,
                    'default'    => '0.00',
                ],
            ]);
        }

        $add = [];
        if (!isset($existing['payment_reference'])) {
            // The bank UTR / UPI reference / cheque no. of the payout the admin actually made.
            $add['payment_reference'] = [
                'type'       => 'VARCHAR',
                'constraint' => '128',
                'NULL'       => TRUE,
            ];
        }
        if (!isset($existing['processed_by'])) {
            $add['processed_by'] = [
                'type'       => 'INT',
                'constraint' => '11',
                'NULL'       => TRUE,
            ];
        }
        if (!isset($existing['processed_at'])) {
            $add['processed_at'] = [
                'type' => 'DATETIME',
                'NULL' => TRUE,
            ];
        }
        if (!empty($add)) {
            $this->dbforge->add_column('payment_requests', $add);
        }

        $this->add_index_if_missing('payment_requests', 'idx_payment_requests_status', '`status`');
        $this->add_index_if_missing('payment_requests', 'idx_payment_requests_type_status', '`payment_type`, `status`');

        // --- seller_settlements ----------------------------------------------------------
        $this->add_index_if_missing('seller_settlements', 'idx_seller_settlements_order', '`order_id`');
    }

    /**
     * CI3's dbforge has no add_key() for an existing table and no "IF NOT EXISTS" for
     * indexes, so a replay would otherwise die on "Duplicate key name".
     */
    private function add_index_if_missing($table, $key_name, $columns)
    {
        if (!$this->db->table_exists($table)) {
            return;
        }
        $existing = $this->db->query(
            'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ' . $this->db->escape($key_name)
        )->num_rows();

        if ($existing == 0) {
            $this->db->query('ALTER TABLE `' . $table . '` ADD KEY `' . $key_name . '` (' . $columns . ')');
        }
    }

    public function down()
    {
        foreach (['payment_reference', 'processed_by', 'processed_at'] as $column) {
            if ($this->db->field_exists($column, 'payment_requests')) {
                $this->dbforge->drop_column('payment_requests', $column);
            }
        }
    }
}
