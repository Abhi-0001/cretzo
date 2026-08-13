<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_fix_email_stored_as_mobile extends CI_Migration
{
    public function up()
    {
        $rows = $this->db
            ->select('id')
            ->from('users')
            ->where("mobile NOT REGEXP '^[0-9]+$'", null, false)
            ->where("email LIKE CONCAT(mobile, '%')", null, false)
            ->get()
            ->result_array();

        foreach ($rows as $row) {
            $this->db->where('id', $row['id'])->update('users', [
                'mobile' => generate_unique_placeholder_mobile(),
            ]);
        }
    }

    public function down()
    {
        // Not reversible - the original (truncated, already-broken) mobile values are gone.
    }
}
