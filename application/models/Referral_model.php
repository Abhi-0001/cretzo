<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Read models for the admin referral screens (phase 3).
 *
 * The two list methods answer bootstrap-table's server-side pagination the way
 * every other admin list in this panel does - reading `offset`, `limit`, `sort`,
 * `order` and `search` from the query string and printing
 * {"total": n, "rows": [...]}. They print rather than return, which is this
 * codebase's convention for these feeds.
 *
 * Nothing here writes. Every change an admin makes to money goes through
 * Referral_engine, so that the rules about caps, holds and never-negative
 * wallets have exactly one implementation.
 */
class Referral_model extends CI_Model
{
    /** The symbol every other admin list prefixes its amounts with. */
    private function money($amount)
    {
        return get_settings('currency') . number_format((float) $amount, 2);
    }

    /**
     * Who referred whom: the attribution ledger.
     *
     * Deliberately shows referrals that have earned nothing yet, and referrals
     * whose programme is switched off - the question "who invited this person"
     * has an answer regardless of whether any money followed, and that answer is
     * what an admin needs when a customer writes in about a missing reward.
     */
    public function ledger_list()
    {
        list($offset, $limit, $sort, $order, $search) = $this->list_params('r.id');

        $searchable = [
            'r.id'          => $search,
            'r.code_used'   => $search,
            'referrer.username' => $search,
            'referrer.mobile'   => $search,
            'referee.username'  => $search,
            'referee.mobile'    => $search,
            'r.status'      => $search,
            'r.signup_ip'   => $search,
        ];

        $count = $this->db->select('COUNT(r.id) AS total', false)
            ->from('referrals r')
            ->join('users referrer', 'referrer.id = r.referrer_id', 'left')
            ->join('users referee', 'referee.id = r.referee_id', 'left');
        if ($search !== '') {
            $count->group_start()->or_like($searchable)->group_end();
        }
        $total = (int) $count->get()->row_array()['total'];

        $rows_q = $this->db->select("r.*,
                referrer.username AS referrer_name, referrer.mobile AS referrer_mobile,
                referee.username AS referee_name, referee.mobile AS referee_mobile,
                p.name AS program_name,
                (SELECT COALESCE(SUM(amount), 0) FROM referral_rewards rw
                    WHERE rw.referral_id = r.id AND rw.status = 'credited') AS paid,
                (SELECT COUNT(id) FROM referral_rewards rw2 WHERE rw2.referral_id = r.id) AS reward_count", false)
            ->from('referrals r')
            ->join('users referrer', 'referrer.id = r.referrer_id', 'left')
            ->join('users referee', 'referee.id = r.referee_id', 'left')
            ->join('referral_programs p', 'p.id = r.program_id', 'left');
        if ($search !== '') {
            $rows_q->group_start()->or_like($searchable)->group_end();
        }
        $result = $rows_q->order_by($sort, $order)->limit($limit, $offset)->get()->result_array();

        $rows = [];
        foreach ($result as $row) {
            $row = output_escaping($row);
            $rows[] = [
                'id'       => $row['id'],
                'referrer' => $this->person($row['referrer_name'], $row['referrer_mobile'], $row['referrer_id']),
                'referee'  => $this->person($row['referee_name'], $row['referee_mobile'], $row['referee_id']),
                'program'  => !empty($row['program_name']) ? $row['program_name'] : '<span class="czr-faint">unmatched</span>',
                'code'     => '<span class="czr-code">' . $row['code_used'] . '</span>',
                'source'   => $this->source_pill($row),
                'status'   => $this->referral_status_pill($row),
                'paid'     => '<span class="czr-strong">' . $this->money($row['paid']) . '</span>',
                'rewards'  => $row['reward_count'],
                'signup_ip' => !empty($row['signup_ip']) ? $row['signup_ip'] : '-',
                'created_at' => date('d-m-Y H:i', strtotime($row['created_at'])),
            ];
        }

        print_r(json_encode(['total' => $total, 'rows' => $rows]));
    }

    /**
     * The money: every reward, filtered by status through ?status= so the review
     * queue and the full history are the same feed.
     */
    public function rewards_list()
    {
        list($offset, $limit, $sort, $order, $search) = $this->list_params('rw.id');
        $status = $this->input->get('status', true);

        $searchable = [
            'rw.id'                 => $search,
            'beneficiary.username'  => $search,
            'beneficiary.mobile'    => $search,
            'rw.status'             => $search,
            'rw.flag_reason'        => $search,
            'rw.source_order_id'    => $search,
        ];

        $apply = function ($builder) use ($search, $searchable, $status) {
            $builder->from('referral_rewards rw')
                ->join('users beneficiary', 'beneficiary.id = rw.beneficiary_id', 'left')
                ->join('referrals r', 'r.id = rw.referral_id', 'left')
                ->join('referral_milestones m', 'm.id = rw.milestone_id', 'left')
                ->join('referral_programs p', 'p.id = r.program_id', 'left');

            /* "queue" is not a status but a working set: everything a human still
             * has to decide about - flagged money that has not been paid. */
            if ($status === 'queue') {
                $builder->where('rw.status', 'pending')->where('rw.flagged', 1);
            } elseif (!empty($status) && $status !== 'all') {
                $builder->where('rw.status', $status);
            }

            if ($search !== '') {
                $builder->group_start()->or_like($searchable)->group_end();
            }

            return $builder;
        };

        $total = (int) $apply($this->db->select('COUNT(rw.id) AS total', false))->get()->row_array()['total'];

        $result = $apply($this->db->select("rw.*,
                beneficiary.username AS beneficiary_name, beneficiary.mobile AS beneficiary_mobile,
                m.name AS milestone_name, p.name AS program_name,
                r.referrer_id, r.referee_id", false))
            ->order_by($sort, $order)->limit($limit, $offset)->get()->result_array();

        $rows = [];
        foreach ($result as $row) {
            $row = output_escaping($row);
            $rows[] = [
                'id'          => $row['id'],
                'beneficiary' => $this->person($row['beneficiary_name'], $row['beneficiary_mobile'], $row['beneficiary_id']),
                'program'     => !empty($row['program_name']) ? $row['program_name'] : '-',
                'milestone'   => !empty($row['milestone_name']) ? $row['milestone_name'] : '-',
                'amount'      => '<span class="czr-strong">' . $this->money($row['amount']) . '</span>'
                    . ((float) $row['reversed_shortfall'] > 0
                        ? '<span class="czr-person__meta" style="color:var(--czr-red);">' . $this->money($row['reversed_shortfall']) . ' unrecovered</span>'
                        : ''),
                'status'      => $this->reward_status_pill($row),
                'order'       => !empty($row['source_order_id'])
                    ? '<a href="' . base_url('admin/orders/index?order_id=' . $row['source_order_id']) . '">#' . $row['source_order_id'] . '</a>'
                    : '-',
                'due'         => !empty($row['qualified_at']) ? date('d-m-Y H:i', strtotime($row['qualified_at'])) : '-',
                'note'        => !empty($row['note']) ? '<small>' . $row['note'] . '</small>' : '',
                'operate'     => $this->reward_actions($row),
            ];
        }

        print_r(json_encode(['total' => $total, 'rows' => $rows]));
    }

    /** Programmes with their milestones, for the configuration screen. */
    public function programs_with_milestones()
    {
        $programs = $this->db->select('*')->order_by('id', 'asc')->get('referral_programs')->result_array();

        foreach ($programs as &$program) {
            $program['milestones'] = $this->db->select('*')
                ->where('program_id', $program['id'])
                ->order_by('sequence', 'asc')
                ->get('referral_milestones')
                ->result_array();
        }

        return $programs;
    }

    // ---------------------------------------------------------------- helpers

    private function list_params($default_sort)
    {
        $offset = (int) ($this->input->get('offset') ?: 0);
        $limit  = (int) ($this->input->get('limit') ?: 10);
        $sort   = $this->input->get('sort', true);
        $order  = strtolower((string) $this->input->get('order', true)) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $this->input->get('search', true));

        /* bootstrap-table sends the COLUMN NAME it is sorting by, which is a
         * display field, not necessarily a column. Anything unrecognised falls
         * back to the primary key rather than reaching the query - an unchecked
         * value here would be an injection point in ORDER BY. */
        $sortable = [
            'id'         => $default_sort,
            'created_at' => 'created_at',
            'due'        => 'qualified_at',
            'amount'     => 'amount',
            'status'     => 'status',
            'paid'       => 'paid',
        ];
        $sort = isset($sortable[$sort]) ? $sortable[$sort] : $default_sort;

        return [$offset, $limit, $sort, $order, $search];
    }

    /* The pills and person cells below emit czr-* classes rather than Bootstrap's
     * badge-* / text-muted. Both referral lists render inside the redesigned screens
     * (assets/admin/css/cretzo/admin-referral.css), and a badge-secondary next to a
     * czr-pill was the giveaway that these tables were assembled from a different
     * kit than the pages holding them. */

    private function person($name, $mobile, $id)
    {
        $name = !empty($name) ? $name : 'User #' . $id;
        $line = '<span class="czr-person__name">' . $name . '</span>';
        if (!empty($mobile)) {
            $line .= '<span class="czr-person__meta">' . $mobile . '</span>';
        }
        return $line;
    }

    /**
     * How the code reached the person who used it. Blank for referrals made
     * before the column existed - shown as a dash rather than guessed at.
     */
    private function source_pill($row)
    {
        $source = isset($row['signup_source']) ? $row['signup_source'] : '';

        $map = [
            'qr'    => ['QR scan', 'info'],
            'link'  => ['Share link', 'off'],
            'typed' => ['Typed in', 'off'],
        ];

        if (!isset($map[$source])) {
            return '<span class="czr-faint">&mdash;</span>';
        }

        return '<span class="czr-pill czr-pill--' . $map[$source][1] . '">' . $map[$source][0] . '</span>';
    }

    private function referral_status_pill($row)
    {
        $map = [
            'attributed' => 'off',
            'active'     => 'info',
            'completed'  => 'live',
            'rejected'   => 'danger',
        ];
        $colour = isset($map[$row['status']]) ? $map[$row['status']] : 'off';

        $pill = '<span class="czr-pill czr-pill--' . $colour . '">' . ucfirst($row['status']) . '</span>';

        if (!empty($row['flagged'])) {
            $pill .= ' <span class="czr-pill czr-pill--warn" title="' . $row['flag_reason'] . '">flagged</span>';
        }

        return $pill;
    }

    private function reward_status_pill($row)
    {
        $map = [
            'pending'   => 'warn',
            'qualified' => 'info',
            'credited'  => 'live',
            'rejected'  => 'danger',
            'reversed'  => 'off',
        ];
        $colour = isset($map[$row['status']]) ? $map[$row['status']] : 'off';

        $pill = '<span class="czr-pill czr-pill--' . $colour . '">' . ucfirst($row['status']) . '</span>';

        if (!empty($row['flagged']) && $row['status'] === 'pending') {
            $pill .= '<span class="czr-person__meta" style="color:var(--czr-red);" title="' . $row['flag_reason'] . '">' . $row['flag_reason'] . '</span>';
        }

        return $pill;
    }

    /**
     * Only the actions that are actually possible on this row. A credited reward
     * cannot be "rejected" - taking paid money back is a reversal and goes
     * through the wallet - and a closed one offers nothing at all.
     */
    private function reward_actions($row)
    {
        if (!has_permissions('update', 'referral')) {
            return '';
        }

        $id = (int) $row['id'];

        /* czr-* classes, not Bootstrap's btn-success / btn-outline-*: these buttons render
         * inside the redesigned queue (assets/admin/css/cretzo/admin-referral.css) and were
         * the last thing on that screen still wearing raw AdminLTE styling. */
        if ($row['status'] === 'pending' || $row['status'] === 'qualified') {
            return '<span class="czr-rowactions">'
                . '<button class="czr-btn czr-btn--good czr-btn--sm referral-review" data-id="' . $id . '" data-action="approve">Approve</button>'
                . '<button class="czr-btn czr-btn--danger czr-btn--sm referral-review" data-id="' . $id . '" data-action="reject">Reject</button>'
                . '</span>';
        }

        if ($row['status'] === 'credited') {
            return '<span class="czr-rowactions">'
                . '<button class="czr-btn czr-btn--ghost czr-btn--sm referral-review" data-id="' . $id . '" data-action="reverse">Reverse</button>'
                . '</span>';
        }

        return '<span class="czr-faint">&mdash;</span>';
    }
}
