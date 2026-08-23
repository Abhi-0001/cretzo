<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Repairs `addresses`.`city_id`, which points at the wrong city on almost every row.
 *
 * The address form is pincode-first: it fills the `city` text box from the pincode lookup and
 * leaves the hidden `city_id` field at whatever it already held. `cities` is seeded with 18 demo
 * cities (Mumbai, Pune, Bangalore, ...) and contains no South Delhi or Kotdwara, so the lookup
 * could not set an id and a stale 1 - Mumbai, the first row - was saved instead.
 *
 * On this database that left 11 of 14 addresses claiming Mumbai for New Delhi / South Delhi /
 * Kotdwara addresses, and 3 pointing at a city_id of 0. Everything that joins on the column read
 * the wrong city: admin order filtering by city, the local-delivery zone lookup, the invoice
 * city, and (until it was changed to prefer the text) the billing_city sent to Shiprocket.
 *
 * What this does, per address:
 *   - if `city` matches a row in `cities` (case-insensitive), point city_id at THAT row;
 *   - otherwise set city_id to 0, because honestly absent beats confidently wrong. Every read
 *     path falls back to the `city` text, which is what the customer entered and what the
 *     courier needs.
 *
 * It deliberately does NOT invent `cities` rows for the missing cities: that table carries a
 * district_id FK into a curated hierarchy, and fabricating entries to satisfy a join would put
 * junk into master data. Address_model::set_address() now derives the id the same way, so new
 * and edited addresses stay consistent without this running again.
 *
 * The `city` text itself is never touched - it is the trustworthy column here.
 */
class Migration_repair_address_city_id extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('addresses') || !$this->db->table_exists('cities')) {
            return;
        }

        // city name (lowercased) => city_id
        $cities = [];
        foreach ($this->db->select('city_id, city_name')->get('cities')->result_array() as $c) {
            $cities[strtolower(trim($c['city_name']))] = (int) $c['city_id'];
        }

        $rows = $this->db->select('id, city, city_id')->get('addresses')->result_array();

        $corrected = $cleared = $already = 0;

        foreach ($rows as $row) {
            $text = strtolower(trim((string) $row['city']));
            $current = (int) $row['city_id'];
            $target = ($text !== '' && isset($cities[$text])) ? $cities[$text] : 0;

            if ($target === $current) {
                $already++;
                continue;
            }

            $this->db->set('city_id', $target)->where('id', $row['id'])->update('addresses');

            if ($target > 0) {
                $corrected++;
            } else {
                $cleared++;
            }

            log_message(
                'error',
                'migration 060: address ' . $row['id'] . ' city="' . $row['city'] . '" city_id '
                    . $current . ' -> ' . $target
            );
        }

        log_message(
            'error',
            'migration 060: ' . count($rows) . ' address(es) examined - ' . $corrected . ' repointed, '
                . $cleared . ' cleared to 0 (city not in `cities`), ' . $already . ' already correct.'
        );
    }

    public function down()
    {
        // Nothing to undo: the previous values were wrong, and the correct ones are derivable
        // from the `city` text at any time by re-running up().
    }
}
