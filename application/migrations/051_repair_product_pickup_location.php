<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Repairs `products.pickup_location` so it actually resolves to a `pickup_locations` row.
 *
 * The column holds a pickup location's NICKNAME and is matched EXACTLY against
 * pickup_locations.pickup_location - by create_shiprocket_order() to find the pickup pincode, and
 * by check_cart_products_delivarable() to ask Shiprocket whether an address is serviceable. Two
 * things stopped it matching:
 *
 *  1. Product_model::add_product() ran the whole payload through escape_array() before writing,
 *     so "Developer's Den" was stored as "Developer\'s Den" while the pickup_locations row holds
 *     the plain form. Verified on this database: all 12 products that name a pickup location
 *     matched nothing, so no shipment could be booked for any of them. The write path is fixed;
 *     this repairs what is already stored.
 *
 *  2. 193 products hold the literal four-character string "NULL" and 85 hold a single space,
 *     both meaning "none set". Those are normalised to an empty string so "is a pickup location
 *     configured?" is a single honest check rather than three special cases.
 *
 * The unescaping is done in SQL with REPLACE rather than PHP's stripcslashes so it is one
 * statement, and it only removes the backslash-before-quote sequences escape_array() produces -
 * it will not touch a backslash a seller genuinely typed followed by anything else.
 */
class Migration_repair_product_pickup_location extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('products')) {
            return;
        }

        // 1. Normalise the "nothing set" placeholders.
        $this->db->query("UPDATE `products` SET `pickup_location` = '' WHERE TRIM(COALESCE(`pickup_location`, '')) = '' OR `pickup_location` = 'NULL'");

        // 2. Strip the SQL escaping escape_array() added: \' -> ' and \" -> "
        $this->db->query("UPDATE `products` SET `pickup_location` = REPLACE(`pickup_location`, CONCAT(CHAR(92), CHAR(39)), CHAR(39)) WHERE INSTR(`pickup_location`, CONCAT(CHAR(92), CHAR(39))) > 0");
        $this->db->query("UPDATE `products` SET `pickup_location` = REPLACE(`pickup_location`, CONCAT(CHAR(92), CHAR(34)), CHAR(34)) WHERE INSTR(`pickup_location`, CONCAT(CHAR(92), CHAR(34))) > 0");

        // Same treatment for the pickup_locations table itself - rows added before the write path
        // was corrected carry the escaping on their own nickname, which would keep the two sides
        // apart from the other direction.
        if ($this->db->table_exists('pickup_locations')) {
            foreach (['pickup_location', 'name', 'address', 'address_2', 'city', 'state', 'country'] as $column) {
                $this->db->query("UPDATE `pickup_locations` SET `" . $column . "` = REPLACE(`" . $column . "`, CONCAT(CHAR(92), CHAR(39)), CHAR(39)) WHERE INSTR(`" . $column . "`, CONCAT(CHAR(92), CHAR(39))) > 0");
                $this->db->query("UPDATE `pickup_locations` SET `" . $column . "` = REPLACE(`" . $column . "`, CONCAT(CHAR(92), CHAR(34)), CHAR(34)) WHERE INSTR(`" . $column . "`, CONCAT(CHAR(92), CHAR(34))) > 0");
            }
        }

        // `order_tracking` rows whose order no longer exists can never sync or be acted on again;
        // they only make the reconciliation cron do pointless work. One such row exists here.
        if ($this->db->table_exists('order_tracking') && $this->db->table_exists('orders')) {
            $this->db->query('DELETE ot FROM `order_tracking` ot LEFT JOIN `orders` o ON o.`id` = ot.`order_id` WHERE o.`id` IS NULL');
        }
    }

    public function down()
    {
        // Re-introducing the escaping (or the literal string "NULL") would only restore the bug.
    }
}
