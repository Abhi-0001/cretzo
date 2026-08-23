<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Stops social signups from being given an invented mobile number.
 *
 * `users`.`mobile` is NOT NULL + UNIQUE, so a Google/Facebook signup - which never supplies a
 * phone number - could not store an empty string: the second such account collided on the
 * unique index with a raw duplicate-key error. The workaround was
 * generate_unique_placeholder_mobile(), which returns '9' followed by nine random digits. That
 * is indistinguishable from a real Indian mobile, so it was displayed as fact: the storefront
 * profile header printed "+91 9018721678" under a customer who had never given a number, and
 * the admin Customers table listed it in the MOBILE NO column.
 *
 * The column is made nullable instead. MySQL's UNIQUE index permits any number of NULLs, so
 * "no mobile" no longer needs a fake unique value. The three call sites now store NULL and the
 * views hide the field when it is empty.
 *
 * ---------------------------------------------------------------------------------------
 * The backfill is deliberately narrow, because a generated number cannot be told apart from a
 * real one by looking at it. A row is only cleared when ALL THREE hold:
 *
 *   1. the account is a social signup (type google / facebook) - phone signups typed their
 *      number in, so none of theirs is ever touched;
 *   2. the value matches the generator's exact signature, ^9[0-9]{9}$ - it always prefixes '9'
 *      and pads to nine digits. This alone spares accounts whose stored number starts with
 *      anything else (8800703170, 8920164976, 0001000100 on this database);
 *   3. the number appears on NONE of that user's own orders or addresses. A generated value
 *      exists only in this column; a number the customer actually uses shows up where they
 *      typed it.
 *
 * Condition 3 is what makes this safe rather than a guess. On this database it is the
 * difference between the 16 rows that are cleared and users.id 8, whose 9910919035 carries 9
 * orders and 1 address and is therefore left exactly as it is.
 *
 * Not reversible: a generated number holds no information worth restoring.
 */
class Migration_null_placeholder_mobiles extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('users') || !$this->db->field_exists('mobile', 'users')) {
            return;
        }

        // NULLable so "this customer has no phone number" is representable at all. The UNIQUE
        // index stays - MySQL does not consider two NULLs equal, so it no longer forces an
        // invented value on every account that lacks one.
        $this->db->query('ALTER TABLE `users` MODIFY `mobile` VARCHAR(15) NULL DEFAULT NULL');

        if (!$this->db->field_exists('type', 'users')) {
            return;
        }

        $sql = "UPDATE `users` u
                   SET u.`mobile` = NULL
                 WHERE u.`type` IN ('google', 'facebook')
                   AND u.`mobile` REGEXP '^9[0-9]{9}$'";

        // Only skip the order/address cross-check if those tables are absent on this install;
        // never widen the match silently.
        if ($this->db->table_exists('orders')) {
            $sql .= " AND NOT EXISTS (SELECT 1 FROM `orders` o
                                       WHERE o.`user_id` = u.`id` AND o.`mobile` = u.`mobile`)";
        }
        if ($this->db->table_exists('addresses')) {
            $sql .= " AND NOT EXISTS (SELECT 1 FROM `addresses` a
                                       WHERE a.`user_id` = u.`id` AND a.`mobile` = u.`mobile`)";
        }

        $this->db->query($sql);
        log_message('info', 'Migration 061: cleared ' . $this->db->affected_rows() . ' placeholder mobile number(s).');
    }

    public function down()
    {
        // The generated numbers carried no information, so there is nothing to restore. The
        // column is left nullable on purpose: reinstating NOT NULL would fail against any row
        // legitimately holding NULL.
    }
}
