<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin approval for major seller-profile edits.
 *
 * Applies only to sellers who are already APPROVED (seller_data.status = 1). Until then the admin
 * reviews the whole profile anyway, so edits go straight through. For an approved seller, any
 * field in MAJOR_FIELDS that the form tries to change is held back: the profile keeps its approved
 * value, and the proposed one waits in seller_profile_change_requests for an admin to approve or
 * reject. Everything else (store name, logo, banner, description, pickup address, categories,
 * password ...) saves immediately.
 *
 * To change what counts as "major", edit MAJOR_FIELDS - it is the only place that decides.
 */
class Seller_profile_change_model extends CI_Model
{
    /** field (seller_data column) => label shown to the seller and the admin */
    const MAJOR_FIELDS = [
        // Personal
        'first_name'                      => 'First name',
        'middle_name'                     => 'Middle name',
        'last_name'                       => 'Last name',
        'phone'                           => 'Phone number',
        'email'                           => 'Email',
        'address1'                        => 'Personal address',
        'district'                        => 'Personal district',
        'city'                            => 'Personal city',
        'state'                           => 'Personal state',
        'pin'                             => 'Personal PIN code',
        'national_identity_card'          => 'Identity proof',
        'address_proof'                   => 'Address proof',
        'authorized_signature'            => 'Authorised signature',
        // Business
        'entity_type'                     => 'Business type',
        'legal_business_name'             => 'Legal business name',
        'pan'                             => 'PAN',
        'gst'                             => 'GSTIN',
        'is_gst_registered'               => 'GST registration status',
        'gst_enrollment_number'           => 'GST enrollment ID',
        'business_address1'               => 'Business address',
        'business_district'               => 'Business district',
        'business_city'                   => 'Business city',
        'business_state'                  => 'Business state',
        'business_pin'                    => 'Business PIN code',
        'pan_card_document'               => 'PAN card document',
        'gstin_document'                  => 'GSTIN document',
        'gst_enrollment_ack_document'     => 'GST enrollment acknowledgement',
        'business_proof_document'         => 'Business proof',
        'business_address_proof_document' => 'Business address proof',
        'partnership_deed_document'       => 'Partnership deed',
        'bank_account_proof_document'     => 'Bank account proof',
        // Bank
        'account_holder_name'             => 'Account holder name',
        'account_number'                  => 'Bank account number',
        'ifsc'                            => 'IFSC code',
        'branch'                          => 'Bank branch',
        'bank_name'                       => 'Bank name',
    ];

    /** Fields that hold an uploaded file's path. */
    const FILE_FIELDS = [
        'national_identity_card', 'address_proof', 'authorized_signature',
        'pan_card_document', 'gstin_document', 'gst_enrollment_ack_document',
        'business_proof_document', 'business_address_proof_document',
        'partnership_deed_document', 'bank_account_proof_document',
    ];

    /** Fields compared upper-case (identifiers), account numbers ignore spaces. */
    private $upper_fields = ['pan', 'gst', 'ifsc', 'gst_enrollment_number'];

    public function table_ready()
    {
        return $this->db->table_exists('seller_profile_change_requests');
    }

    public function is_approved_seller($seller_id)
    {
        $row = $this->db->select('status')->where('user_id', (int) $seller_id)->get('seller_data')->row_array();
        return !empty($row) && (string) $row['status'] === '1';
    }

    public function get_pending($seller_id)
    {
        if (!$this->table_ready()) {
            return null;
        }
        $row = $this->db->where(['seller_id' => (int) $seller_id, 'status' => 'pending'])
            ->order_by('id', 'DESC')->limit(1)->get('seller_profile_change_requests')->row_array();
        if (empty($row)) {
            return null;
        }
        $row['changes'] = json_decode($row['changes'], true) ?: [];
        return $row;
    }

    public function count_pending()
    {
        return $this->table_ready()
            ? (int) $this->db->where('status', 'pending')->count_all_results('seller_profile_change_requests')
            : 0;
    }

    /** Pending values laid over a profile row, so the form shows what the seller asked for. */
    public function overlay_pending(array $row, $seller_id)
    {
        $pending = $this->get_pending($seller_id);
        if (!$pending) {
            return $row;
        }
        foreach ($pending['changes'] as $field => $c) {
            $row[$field] = $c['new'];
        }
        return $row;
    }

    private function norm($field, $value)
    {
        $v = trim((string) ($value === null ? '' : $value));
        if (in_array($field, self::FILE_FIELDS, true)) {
            return $v;
        }
        $v = preg_replace('/\s+/', ' ', $v);
        if ($field === 'account_number') {
            $v = str_replace(' ', '', $v);
        }
        return in_array($field, $this->upper_fields, true) ? strtoupper($v) : strtolower($v);
    }

    /** True when $value is just the seller's approved first + last name (entity type Individual). */
    private function is_own_name($value, array $current)
    {
        if (strtolower((string) ($current['entity_type'] ?? '')) !== 'individual' && trim((string) ($current['entity_type'] ?? '')) !== '') {
            return false;
        }
        $full = trim(preg_replace('/\s+/', ' ', trim((string) ($current['first_name'] ?? '')) . ' ' . trim((string) ($current['last_name'] ?? ''))));
        return $full !== '' && strcasecmp(trim(preg_replace('/\s+/', ' ', (string) $value)), $full) === 0;
    }

    /**
     * Hold back every major change in a profile save.
     *
     * $seller_data / $seller_profile are the arrays about to go to Seller_model::add_seller(); the
     * held fields are put back to their approved values IN PLACE, so the normal save then writes
     * only what is allowed to change.
     *
     * @return array ['held' => [label, ...], 'request_id' => int|null, 'created' => bool]
     */
    public function hold_major_changes($seller_id, array &$seller_data, array &$seller_profile)
    {
        $out = ['held' => [], 'request_id' => null, 'created' => false];
        if (!$this->table_ready() || !$this->is_approved_seller($seller_id)) {
            return $out;
        }

        $current = unslash_deep(seller_profile_row($seller_id));
        $user = $this->db->select('username, address')->where('id', (int) $seller_id)->get('users')->row_array();
        $pending = $this->get_pending($seller_id);

        $changes = [];
        foreach (self::MAJOR_FIELDS as $field => $label) {
            if (!array_key_exists($field, $seller_data)) {
                continue;
            }
            $posted = $seller_data[$field];
            $now = isset($current[$field]) ? $current[$field] : '';
            if ($this->norm($field, $posted) === $this->norm($field, $now)) {
                continue;
            }
            // For an Individual the form fills an empty legal name with the seller's own name. That
            // is their already-approved first + last name, so there is nothing new to approve -
            // without this, every save by a seller who never had a legal name filed a request.
            if ($field === 'legal_business_name' && trim((string) $now) === '' && $this->is_own_name($posted, $current)) {
                continue;
            }
            $changes[$field] = ['label' => $label, 'old' => (string) $now, 'new' => (string) $posted];
            $seller_data[$field] = $now; // keep the approved value live
        }

        // users.username / users.address mirror first_name / address1 on every save.
        if (isset($changes['first_name']) && isset($seller_profile['name'])) {
            $seller_profile['name'] = $user['username'];
        }
        if (isset($changes['address1']) && isset($seller_profile['address'])) {
            $seller_profile['address'] = $user['address'];
        }

        if (empty($changes)) {
            // Every field is back at its approved value: withdraw any waiting request.
            if ($pending) {
                $this->discard_files($pending['changes'], []);
                $this->db->where('id', $pending['id'])->delete('seller_profile_change_requests');
            }
            return $out;
        }

        $json = json_encode($changes, JSON_UNESCAPED_UNICODE);
        if ($pending) {
            $this->discard_files($pending['changes'], $changes);
            $this->db->where('id', $pending['id'])->update('seller_profile_change_requests', [
                'changes' => $json, 'requested_at' => date('Y-m-d H:i:s'),
            ]);
            $out['request_id'] = (int) $pending['id'];
        } else {
            $this->db->insert('seller_profile_change_requests', [
                'seller_id' => (int) $seller_id, 'changes' => $json, 'status' => 'pending',
            ]);
            $out['request_id'] = (int) $this->db->insert_id();
            $out['created'] = true;
            $name = !empty($user['username']) ? $user['username'] : ('Seller #' . $seller_id);
            $this->db->insert('system_notification', [
                'title'   => 'Seller profile change needs approval',
                'message' => $name . ' has changed ' . implode(', ', array_column($changes, 'label')) . '. Review and approve or reject it under Sellers > Profile Changes.',
                'type'    => 'seller_profile_change',
                'type_id' => $out['request_id'],
                'read_by' => 0,
            ]);
        }
        $out['held'] = array_values(array_column($changes, 'label'));
        return $out;
    }

    /**
     * Approve: write the proposed values to seller_data (and users for name/address).
     * @return array [error(bool), message]
     */
    public function approve($request_id, $admin_id)
    {
        $req = $this->get_request($request_id);
        if (!$req || $req['status'] !== 'pending') {
            return [true, 'This request is no longer pending.'];
        }
        $set = [];
        foreach ($req['changes'] as $field => $c) {
            if (isset(self::MAJOR_FIELDS[$field])) {
                $set[$field] = $c['new'];
            }
        }
        $seller_id = (int) $req['seller_id'];
        if (!empty($set)) {
            $this->db->where('user_id', $seller_id)->update('seller_data', $set);
        }
        $users = [];
        if (isset($set['first_name'])) {
            $users['username'] = $set['first_name'];
        }
        if (isset($set['address1'])) {
            $users['address'] = $set['address1'];
        }
        if (!empty($users)) {
            $this->db->where('id', $seller_id)->update('users', $users);
        }
        // The superseded documents are no longer referenced by the profile.
        foreach ($req['changes'] as $field => $c) {
            if (in_array($field, self::FILE_FIELDS, true) && $c['old'] !== '' && $c['old'] !== $c['new']) {
                $this->delete_file($c['old']);
            }
        }
        $this->db->where('id', (int) $request_id)->update('seller_profile_change_requests', [
            'status' => 'approved', 'reviewed_at' => date('Y-m-d H:i:s'), 'reviewed_by' => (int) $admin_id,
        ]);
        add_user_notification($seller_id, 'Profile changes approved',
            'Your changes to ' . implode(', ', array_column($req['changes'], 'label')) . ' were approved and are now live.',
            'seller_profile_change', 'seller/home/profile', $request_id);
        return [false, 'Changes approved and applied to the seller profile.'];
    }

    /** Reject: the live profile is untouched; the proposed documents are deleted. */
    public function reject($request_id, $admin_id, $reason)
    {
        $req = $this->get_request($request_id);
        if (!$req || $req['status'] !== 'pending') {
            return [true, 'This request is no longer pending.'];
        }
        $reason = mb_substr(trim((string) $reason), 0, 500);
        $this->discard_files($req['changes'], []);
        $this->db->where('id', (int) $request_id)->update('seller_profile_change_requests', [
            'status' => 'rejected', 'reject_reason' => $reason, 'reviewed_at' => date('Y-m-d H:i:s'), 'reviewed_by' => (int) $admin_id,
        ]);
        add_user_notification((int) $req['seller_id'], 'Profile changes not approved',
            'Your changes to ' . implode(', ', array_column($req['changes'], 'label')) . ' were not approved.'
            . ($reason !== '' ? ' Reason: ' . $reason : '') . ' Your previous details are unchanged.',
            'seller_profile_change', 'seller/home/profile', $request_id);
        return [false, 'Changes rejected. The seller has been told.'];
    }

    public function get_request($request_id)
    {
        $row = $this->db->where('id', (int) $request_id)->get('seller_profile_change_requests')->row_array();
        if (empty($row)) {
            return null;
        }
        $row['changes'] = json_decode($row['changes'], true) ?: [];
        return $row;
    }

    /** Requests for the admin screen, newest first, with the seller's name. */
    public function list_requests($status = 'pending')
    {
        $rows = $this->db->select('r.*, u.username, u.mobile, sd.shop_name')
            ->from('seller_profile_change_requests r')
            ->join('users u', 'u.id = r.seller_id', 'left')
            ->join('seller_data sd', 'sd.user_id = r.seller_id', 'left')
            ->where('r.status', $status)
            ->order_by('r.id', 'DESC')->get()->result_array();
        foreach ($rows as &$r) {
            $r['changes'] = json_decode($r['changes'], true) ?: [];
        }
        return $rows;
    }

    /** Delete proposed files from $old_changes that $keep_changes no longer proposes. */
    private function discard_files(array $old_changes, array $keep_changes)
    {
        foreach ($old_changes as $field => $c) {
            if (!in_array($field, self::FILE_FIELDS, true) || empty($c['new']) || $c['new'] === $c['old']) {
                continue;
            }
            if (isset($keep_changes[$field]) && $keep_changes[$field]['new'] === $c['new']) {
                continue;
            }
            $this->delete_file($c['new']);
        }
    }

    private function delete_file($path)
    {
        $path = (string) $path;
        if ($path === '' || strpos($path, SELLER_DOCUMENTS_PATH) !== 0 || strpos($path, '..') !== false) {
            return;
        }
        delete_images(SELLER_DOCUMENTS_PATH, basename($path));
    }
}
