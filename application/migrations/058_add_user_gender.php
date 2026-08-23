<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Adds `gender` to `users` so the storefront profile form can collect it.
 *
 * `users` already carries `dob` (varchar 16, written by the mobile app's
 * update_user_profile endpoint) and `image` (the avatar, uploaded into
 * USER_IMG_PATH), so those two needed no schema change - only the web form was
 * missing them. `gender` existed nowhere in the codebase or the schema.
 *
 * Nullable with no default because the field is deliberately OPTIONAL: every existing
 * account keeps NULL, and "not answered" has to stay distinguishable from a real answer.
 * Stored as the plain word rather than a code so the value is readable in the DB and in
 * the app API payloads that already serve `users` rows verbatim.
 *
 * Accepted values (enforced in the controller, not the column, to match how the rest of
 * this schema handles small value sets): 'male', 'female', 'other', or NULL/''.
 */
class Migration_add_user_gender extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('gender', 'users')) {
            $this->dbforge->add_column('users', [
                'gender' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 16,
                    'null'       => true,
                    'default'    => null,
                    'comment'    => 'male|female|other; NULL = not answered (optional field)',
                    'after'      => 'dob',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('gender', 'users')) {
            $this->dbforge->drop_column('users', 'gender');
        }
    }
}
