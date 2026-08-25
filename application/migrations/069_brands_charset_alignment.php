<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Aligns the `brands` table charset with `products`, so the storefront join can
 * use an index.
 *
 * THE PROBLEM
 * -----------
 * Every storefront read joins:
 *
 *     LEFT JOIN brands b ON p.brand = b.name
 *
 * but the two sides disagree about character set:
 *
 *     products.brand   varchar(256)  utf8mb4 / utf8mb4_unicode_ci
 *     brands.name      varchar(256)  utf8    / utf8_general_ci
 *
 * MySQL cannot use an index on the smaller side of a cross-collation comparison -
 * it has to convert every row first - so `brands` was the last table still being
 * FULL SCANNED in the main product query even after migration 068 added
 * idx_brands_name. Verified: EXPLAIN shows `type=ALL, key=NULL` for the join, while
 * the very same index IS used (`type=ref, key=idx_brands_name`) for a same-collation
 * lookup such as `WHERE b.name = 'Clayya'`.
 *
 * WHY THIS DIRECTION
 * ------------------
 * We widen `brands` to utf8mb4 rather than narrowing `products.brand` to utf8:
 *
 *   - utf8mb4 is a strict SUPERSET of MySQL's 3-byte utf8. Going utf8 -> utf8mb4 is
 *     lossless; every existing value stays byte-for-byte valid.
 *   - The reverse is a NARROWING conversion. Any 4-byte character already stored in
 *     products.brand (an emoji, a less common CJK ideograph) would be corrupted or
 *     rejected. That is a silent data-loss risk on a live table.
 *   - `products` is also the far larger table, and the rest of the schema
 *     (products, categories, product_variants, product_rating) is already
 *     utf8mb4_unicode_ci - so converting `brands` moves it TOWARDS the house
 *     standard rather than away from it.
 *
 * The collation must match exactly, not just the charset: utf8mb4_general_ci would
 * still be a mismatch against utf8mb4_unicode_ci and would still block the index.
 *
 * BEHAVIOUR CHANGE, HONESTLY STATED
 * ---------------------------------
 * utf8_general_ci and utf8mb4_unicode_ci are both case-insensitive, so brand-name
 * uniqueness validation (is_unique[brands.name] in admin/Brand.php) behaves the
 * same. They differ in full Unicode collation: unicode_ci implements UCA, so it
 * treats 'ss' = 'ß' and 'ae' = 'æ' as equal, where general_ci does not. That can
 * affect ORDER BY name and equality on those specific characters.
 *
 * All brand names on this database are ASCII ("Cretzo", "Adidas", "Decathlon"), so
 * there is no practical difference today. It is recorded here because it IS a real
 * semantic change and should not be discovered later by surprise.
 *
 * The 191-character prefix indexes from migration 068 remain valid: 191 x 4 bytes
 * is 764, still inside the 767-byte limit.
 */
class Migration_brands_charset_alignment extends CI_Migration
{
    /** Must match products.brand exactly for the join to be indexable. */
    const TARGET_CHARSET   = 'utf8mb4';
    const TARGET_COLLATION = 'utf8mb4_unicode_ci';

    public function up()
    {
        if (!$this->db->table_exists('brands')) {
            log_message('error', '069_brands_charset_alignment: `brands` table missing, skipped');
            return;
        }

        $current = $this->current_collation('brands', 'name');
        if ($current === self::TARGET_COLLATION) {
            log_message('debug', '069_brands_charset_alignment: already ' . self::TARGET_COLLATION . ', skipped');
            return;
        }

        // Confirm the target really is what products.brand uses. If somebody has since
        // changed that column, converting to a hardcoded guess would leave the two
        // mismatched all over again - better to do nothing and say so.
        $products_collation = $this->current_collation('products', 'brand');
        if ($products_collation !== null && $products_collation !== self::TARGET_COLLATION) {
            log_message(
                'error',
                '069_brands_charset_alignment: products.brand is ' . $products_collation
                . ', not ' . self::TARGET_COLLATION . ' - skipping rather than creating a new mismatch'
            );
            return;
        }

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            // CONVERT TO converts every character column AND the table default, which
            // is what we want: leaving `slug`/`image` on the old charset would just
            // move the same trap somewhere else.
            $sql = 'ALTER TABLE `brands` CONVERT TO CHARACTER SET ' . self::TARGET_CHARSET
                 . ' COLLATE ' . self::TARGET_COLLATION;
            if ($this->db->query($sql) === false) {
                $error = $this->db->error();
                log_message('error', '069_brands_charset_alignment: conversion failed: '
                    . (isset($error['message']) ? $error['message'] : 'unknown error'));
            } else {
                log_message('debug', '069_brands_charset_alignment: brands converted to ' . self::TARGET_COLLATION);
            }
        } finally {
            $this->db->db_debug = $db_debug;
        }
    }

    public function down()
    {
        if (!$this->db->table_exists('brands')) {
            return;
        }
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            // Reverting re-introduces the cross-collation join, and is a NARROWING
            // conversion - any 4-byte character added to a brand name while this
            // migration was applied would be lost. Kept only for completeness.
            $this->db->query('ALTER TABLE `brands` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci');
        } finally {
            $this->db->db_debug = $db_debug;
        }
    }

    private function current_collation($table, $column)
    {
        $row = $this->db->query(
            'SELECT collation_name FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
            [$table, $column]
        )->row_array();
        return isset($row['collation_name']) ? $row['collation_name'] : null;
    }
}
