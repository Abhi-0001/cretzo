<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Widen users.apikey so it can hold a full per-user API token.
 *
 * The seller API now issues a 64-character token at login and checks it with hash_equals on
 * the withdrawal endpoints. The column was VARCHAR(32), so MySQL silently truncated the token
 * on write while the app kept the full string - the comparison then failed for everybody and
 * legitimate callers were locked out, which is the safe direction to fail but still broken.
 *
 * Any token issued before this migration is truncated and therefore unusable, so they are
 * cleared: the next login re-issues a full one. Nothing else reads this column (it was
 * completely unused before the API token work - 0 of 45 rows populated), so clearing it
 * cannot affect anything else.
 */
class Migration_widen_user_api_token extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('apikey', 'users')) {
            return;
        }

        foreach ($this->db->field_data('users') as $field) {
            if ($field->name === 'apikey' && (int) $field->max_length >= 64) {
                return; // already wide enough
            }
        }

        $this->dbforge->modify_column('users', [
            'apikey' => [
                'name'       => 'apikey',
                'type'       => 'VARCHAR',
                'constraint' => '128',
                'NULL'       => TRUE,
            ],
        ]);

        // Truncated tokens can never match; force a clean re-issue at next login.
        $this->db->where('apikey IS NOT NULL', null, false)->update('users', ['apikey' => null]);
    }

    public function down()
    {
        // Not reverted: narrowing this again would truncate live tokens and lock users out.
    }
}
