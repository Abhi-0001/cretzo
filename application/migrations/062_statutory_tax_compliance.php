<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * TCS (GST s.52) and TDS (Income Tax s.194-O) compliance.
 *
 * Until now both were a single flat percentage typed into the settings screen and applied
 * to every seller identically - which is not what either statute says. The rate that
 * actually applies depends on facts about the SELLER:
 *
 *   TDS 194-O : entity class, read off the 4th character of the PAN. An individual / HUF
 *               (P / H) gets a Rs. 5,00,000 per-financial-year threshold before any TDS is
 *               due; a firm / LLP / company (F / L / C) gets none and is deducted from the
 *               first rupee. A seller with no valid PAN is deducted at 5% under s.206AA.
 *   TCS  s.52 : only a GSTIN-registered seller is collected from. The collection splits
 *               into IGST for an inter-state supply, or CGST + SGST for an intra-state one.
 *
 * None of that could be recorded against a settlement, so a statement could not show which
 * rule produced the deduction, and the quarterly TCS return (which needs the IGST/CGST/SGST
 * split) could not be produced from this data at all. These columns make each settlement
 * self-describing: the rates used, the split, the classification they came from, and the
 * financial year plus the cumulative turnover the threshold decision was based on.
 *
 * Every column is additive and defaults to 0 / NULL, so settlements recorded before this
 * read back exactly as they were.
 */
class Migration_statutory_tax_compliance extends CI_Migration
{
    public function up()
    {
        $columns = [
            // Rates actually applied, so a statement can be re-derived without guessing what
            // the settings screen held on the day.
            'tds_percent'          => ['type' => 'DECIMAL', 'constraint' => '5,2', 'NULL' => TRUE, 'default' => 0],
            'tcs_percent'          => ['type' => 'DECIMAL', 'constraint' => '5,2', 'NULL' => TRUE, 'default' => 0],

            // The TCS split. tcs_amount stays the total, so existing readers keep working.
            'tcs_igst_amount'      => ['type' => 'DECIMAL', 'constraint' => '10,2', 'NULL' => TRUE, 'default' => 0],
            'tcs_cgst_amount'      => ['type' => 'DECIMAL', 'constraint' => '10,2', 'NULL' => TRUE, 'default' => 0],
            'tcs_sgst_amount'      => ['type' => 'DECIMAL', 'constraint' => '10,2', 'NULL' => TRUE, 'default' => 0],

            // Why each deduction came out the way it did.
            //   tds_basis  : 'threshold_exempt' | 'sec_194o' | 'sec_206aa_no_pan'
            //   tcs_basis  : 'unregistered' | 'intra_state' | 'inter_state'
            'tds_basis'            => ['type' => 'VARCHAR', 'constraint' => '32', 'NULL' => TRUE],
            'tcs_basis'            => ['type' => 'VARCHAR', 'constraint' => '32', 'NULL' => TRUE],
            // 'individual' | 'huf' | 'firm' | 'llp' | 'company' | 'trust' | 'other' | 'no_pan'
            'seller_entity_class'  => ['type' => 'VARCHAR', 'constraint' => '20', 'NULL' => TRUE],
            'place_of_supply'      => ['type' => 'VARCHAR', 'constraint' => '20', 'NULL' => TRUE],

            // The threshold decision is only auditable if the numbers behind it are kept:
            // which FY was counted, and what the seller's cumulative turnover was BEFORE this
            // line was added to it.
            'financial_year'       => ['type' => 'VARCHAR', 'constraint' => '9', 'NULL' => TRUE],
            'cumulative_turnover'  => ['type' => 'DECIMAL', 'constraint' => '14,2', 'NULL' => TRUE, 'default' => 0],
        ];

        $add = [];
        foreach ($columns as $name => $definition) {
            if (!$this->db->field_exists($name, 'seller_settlements')) {
                $add[$name] = $definition;
            }
        }
        if (!empty($add)) {
            $this->dbforge->add_column('seller_settlements', $add);
        }

        // Statutory rates are seeded into system_settings so the compliance behaviour is ON
        // by default. They were shipped at 0 with a note to leave them alone until an
        // accountant confirmed - which meant a live marketplace collected nothing at all.
        // The values below are the statutory ones; the settings screen can still override
        // them, and the master switch there can still turn the whole thing off.
        $defaults = [
            'statutory_deductions_enabled' => '1',
            'tcs_percent'                  => '0.5',  // GST s.52, on the ex-GST taxable value
            'tds_percent'                  => '0.1',  // s.194-O, valid PAN
            'tds_percent_no_pan'           => '5',    // s.206AA, missing / invalid PAN
            'tds_threshold_amount'         => '500000', // per FY, individual / HUF only
            'enforce_intrastate_unregistered' => '1',
        ];

        $row = $this->db->where('variable', 'system_settings')->get('settings')->row_array();
        if (empty($row)) {
            return;
        }

        $settings = json_decode($row['value'], true);
        if (!is_array($settings)) {
            return;
        }

        $changed = false;
        foreach ($defaults as $key => $value) {
            // Only seed what is absent or was left at the "withhold nothing" placeholder. An
            // admin who has deliberately entered a rate keeps it.
            if (!isset($settings[$key]) || $settings[$key] === '' || $settings[$key] === '0' || $settings[$key] === 0) {
                $settings[$key] = $value;
                $changed = true;
            }
        }

        if ($changed) {
            $this->db->where('variable', 'system_settings')
                ->update('settings', ['value' => json_encode($settings)]);
        }
    }

    public function down()
    {
        foreach ([
            'tds_percent', 'tcs_percent', 'tcs_igst_amount', 'tcs_cgst_amount', 'tcs_sgst_amount',
            'tds_basis', 'tcs_basis', 'seller_entity_class', 'place_of_supply',
            'financial_year', 'cumulative_turnover',
        ] as $column) {
            if ($this->db->field_exists($column, 'seller_settlements')) {
                $this->dbforge->drop_column('seller_settlements', $column);
            }
        }
    }
}
