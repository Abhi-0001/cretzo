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
            // The placeholder MUST be resolved into a variable before the update call, and
            // the WHERE passed as update()'s third argument rather than chained.
            //
            // This previously read:
            //     $this->db->where('id', $row['id'])->update('users', ['mobile' => generate_unique_placeholder_mobile()]);
            //
            // PHP chains where() first and only THEN evaluates the arguments to update().
            // generate_unique_placeholder_mobile() calls is_exist(), which runs its own
            // query, and any query resets CodeIgniter's query builder - including the
            // where('id', ...) still pending on it. The update therefore executed as a
            // bare "UPDATE users SET mobile = '...'" against EVERY row in the table.
            //
            // On production that aborted with "Duplicate entry for key 'mobile_2'" (mobile
            // is UNIQUE), so MySQL rolled the statement back and no data was changed - the
            // unique index is the only reason this was a failed migration and not a silent
            // overwrite of every user's phone number. It passed locally only because no
            // row matched the SELECT above, so this line never ran.
            //
            // Passing the condition as an argument makes it immune to builder resets: all
            // three arguments are evaluated before update() is entered.
            $placeholder = generate_unique_placeholder_mobile();

            $this->db->update('users', ['mobile' => $placeholder], ['id' => $row['id']]);

            // A bare UPDATE would report far more than one row; refuse to keep going if
            // this ever stops being scoped to the single row it is meant to touch.
            if ($this->db->affected_rows() > 1) {
                show_error(
                    'Migration 031 aborted: updating user #' . $row['id'] . ' affected '
                    . $this->db->affected_rows() . ' rows instead of 1.'
                );
            }
        }
    }

    public function down()
    {
        // Not reversible - the original (truncated, already-broken) mobile values are gone.
    }
}
