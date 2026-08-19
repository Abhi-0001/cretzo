<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * End-to-end regression suite for the returns/refunds and promo-code money flows.
 *
 * CLI only - `php index.php ztest run` from the project root. It seeds its own users, products,
 * orders and promo codes, exercises the real helpers and models (no mocks), and deletes
 * everything it created on the way out, so it is safe to run against a development database.
 *
 * Covers: return request creation and de-duplication, admin approve/reject, refund sizing
 * (promo resize, delivery charge, COD, wallet-part-paid, whole-order), refund and stock
 * idempotency across all the paths that can reach the same item, commission clawback,
 * per-seller order_charges rewrite, the customer return window, promo eligibility/validity/
 * usage limits/minimums/discount caps, and the cashback settlement cron.
 */
class Ztest extends CI_Controller
{
    private $pass = 0;
    private $fail = 0;
    private $fails = [];
    private $created = []; // table => [ids]

    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) show_404();
        $this->load->helper('function_helper');
        $this->load->model(['Order_model', 'Seller_model', 'Return_request_model', 'Promo_code_model']);
    }

    /* ---------------- assertions ---------------- */
    private function ok($name, $cond, $detail = '')
    {
        if ($cond) {
            $this->pass++;
            echo "  PASS  $name\n";
        } else {
            $this->fail++;
            $this->fails[] = $name . ($detail ? " -- $detail" : '');
            echo "  FAIL  $name" . ($detail ? "  [$detail]" : '') . "\n";
        }
    }
    private function eq($name, $expected, $actual)
    {
        $c = (is_float($expected) || is_float($actual))
            ? abs(floatval($expected) - floatval($actual)) < 0.011
            : ((string) $expected === (string) $actual);
        $this->ok($name, $c, "expected=" . json_encode($expected) . " actual=" . json_encode($actual));
    }
    private function section($t)
    {
        echo "\n=== $t ===\n";
    }
    private function summary()
    {
        echo "\n-------------------------------------------\n";
        echo "PASS: {$this->pass}   FAIL: {$this->fail}\n";
        foreach ($this->fails as $f) {
            echo "  * $f\n";
        }
        echo "-------------------------------------------\n";
    }

    /* ---------------- seeding ---------------- */
    private function ins($table, $row)
    {
        $this->db->insert($table, $row);
        $id = $this->db->insert_id();
        $this->created[$table][] = $id;
        return $id;
    }
    private function cleanup()
    {
        if (!empty($this->created['orders'])) {
            $this->db->where_in('order_id', $this->created['orders'])->delete('transactions');
            $this->db->where_in('order_id', $this->created['orders'])->delete('seller_settlements');
            $this->db->where_in('order_id', $this->created['orders'])->delete('return_requests');
            $this->db->where_in('order_id', $this->created['orders'])->delete('order_charges');
            $this->db->where_in('order_id', $this->created['orders'])->delete('order_items');
        }
        if (!empty($this->created['users'])) {
            $this->db->where_in('user_id', $this->created['users'])->delete('transactions');
        }
        $order = ['stock_logs', 'return_requests', 'order_charges', 'order_items', 'orders', 'product_variants', 'products', 'promo_codes', 'users'];
        $report = [];
        foreach ($order as $t) {
            if (!empty($this->created[$t])) {
                $want = count(array_unique($this->created[$t]));
                $this->db->where_in('id', array_unique($this->created[$t]))->delete($t);
                $left = $this->db->where_in('id', array_unique($this->created[$t]))->count_all_results($t);
                $report[] = $t . ': ' . ($want - $left) . '/' . $want . ($left ? ' LEFT ' . $left : '');
            }
        }
        // stock_logs rows are written by update_stock() deep inside the code under test, so
        // they are never tracked by ins(). Sweep the ones pointing at orders that no longer
        // exist - which, by this point, is exactly the ones this run created.
        $this->db->where('order_id IS NOT NULL', null, false)
            ->where('order_id !=', 0)
            ->where('order_id NOT IN (SELECT id FROM orders)', null, false)
            ->delete('stock_logs');

        echo chr(10) . 'cleanup -> ' . implode(', ', $report) . chr(10);
    }

    private function mk_user($balance = 0, $name = 'zt_cust')
    {
        return $this->ins('users', [
            'ip_address' => '127.0.0.1',
            'username' => $name . '_' . uniqid(),
            'email' => uniqid('zt') . '@example.test',
            'mobile' => '90000' . rand(10000, 99999),
            'password' => uniqid('p', true),
            'active' => 1,
            'balance' => $balance,
        ]);
    }
    private function mk_product($seller_id, $price = 1000, $stock = 100, $returnable = 1, $stock_type = '1')
    {
        $pid = $this->ins('products', [
            'name' => 'ZT Product ' . uniqid(),
            'slug' => 'zt-product-' . uniqid(),
            'image' => '',
            'category_id' => 1,
            'seller_id' => $seller_id,
            'is_returnable' => $returnable,
            'is_cancelable' => 1,
            'cancelable_till' => 'delivered',
            'stock_type' => $stock_type,
            'stock' => $stock,
            'type' => 'simple_product',
            'status' => 1,
        ]);
        $vid = $this->ins('product_variants', [
            'product_id' => $pid,
            'price' => $price,
            'special_price' => 0,
            'stock' => $stock,
            'status' => 1,
        ]);
        return [$pid, $vid];
    }

    private function mk_order($user_id, $items, $opts = [])
    {
        $o = array_merge([
            'payment_method' => 'RazorPay',
            'promo_code' => '',
            'promo_discount' => 0,
            'delivery_charge' => 0,
            'is_delivery_charge_returnable' => 0,
            'wallet_balance' => 0,
        ], $opts);

        $total = 0;
        foreach ($items as $it) {
            $total += $it['qty'] * $it['price'];
        }
        $final_total = $total + $o['delivery_charge'] - $o['promo_discount'];
        $total_payable = (strtolower($o['payment_method']) == 'cod') ? $final_total - $o['wallet_balance'] : 0;

        $order_id = $this->ins('orders', [
            'user_id' => $user_id,
            'mobile' => '9000000000',
            'total' => $total,
            'delivery_charge' => $o['delivery_charge'],
            'is_delivery_charge_returnable' => $o['is_delivery_charge_returnable'],
            'wallet_balance' => $o['wallet_balance'],
            'promo_code' => $o['promo_code'],
            'promo_discount' => $o['promo_discount'],
            'discount' => 0,
            'total_payable' => $total_payable,
            'final_total' => $final_total,
            'payment_method' => $o['payment_method'],
            'address_id' => 0,
        ]);

        $item_ids = [];
        $status_json = json_encode([['received', date('Y-m-d H:i:s')]]);
        foreach ($items as $it) {
            $sub = $it['qty'] * $it['price'];
            $item_ids[] = $this->ins('order_items', [
                'user_id' => $user_id,
                'order_id' => $order_id,
                'seller_id' => $it['seller_id'],
                'product_name' => 'ZT',
                'variant_name' => 'ZT',
                'product_variant_id' => $it['variant_id'],
                'quantity' => $it['qty'],
                'price' => $it['price'],
                'discounted_price' => 0,
                'tax_percent' => 0,
                'tax_amount' => 0,
                'discount' => 0,
                'sub_total' => $sub,
                'status' => $status_json,
                'active_status' => 'received',
                'is_credited' => 0,
            ]);
        }
        $by_seller = [];
        foreach ($items as $k => $it) {
            $sid = $it['seller_id'];
            if (!isset($by_seller[$sid])) {
                $by_seller[$sid] = ['sub_total' => 0, 'ids' => []];
            }
            $by_seller[$sid]['sub_total'] += $it['qty'] * $it['price'];
            $by_seller[$sid]['ids'][] = $item_ids[$k];
        }
        foreach ($by_seller as $sid => $d) {
            $share = $total > 0 ? $d['sub_total'] / $total : 0;
            $this->ins('order_charges', [
                'seller_id' => $sid,
                'order_id' => $order_id,
                'order_item_ids' => implode(',', $d['ids']),
                'product_variant_ids' => '',
                'delivery_charge' => round($o['delivery_charge'] * $share, 2),
                'promo_code' => $o['promo_code'],
                'promo_discount' => round($o['promo_discount'] * $share, 2),
                'sub_total' => $d['sub_total'],
                'total' => round($d['sub_total'] + $o['delivery_charge'] * $share - $o['promo_discount'] * $share, 2),
            ]);
        }
        return [$order_id, $item_ids];
    }

    private function mk_promo($over = [])
    {
        return $this->ins('promo_codes', array_merge([
            'promo_code' => 'ZT' . strtoupper(substr(uniqid(), -8)),
            'message' => 'test',
            'start_date' => date('Y-m-d', strtotime('-5 days')),
            'end_date' => date('Y-m-d', strtotime('+5 days')),
            'no_of_users' => 100,
            'minimum_order_amount' => 500,
            'discount' => 10,
            'discount_type' => 'percentage',
            'max_discount_amount' => 200,
            'repeat_usage' => 0,
            'no_of_repeat_usage' => 0,
            'status' => 1,
            'is_cashback' => 0,
            'list_promocode' => 1,
        ], $over));
    }
    private function code($id)
    {
        return $this->db->select('promo_code')->where('id', $id)->get('promo_codes')->row()->promo_code;
    }
    private function bal($uid)
    {
        return floatval($this->db->select('balance')->where('id', $uid)->get('users')->row()->balance);
    }
    private function pstock($pid)
    {
        return floatval($this->db->select('stock')->where('id', $pid)->get('products')->row()->stock);
    }
    private function stock($vid)
    {
        return floatval($this->db->select('stock')->where('id', $vid)->get('product_variants')->row()->stock);
    }
    private function item($id)
    {
        return $this->db->where('id', $id)->get('order_items')->row_array();
    }
    private function order($id)
    {
        return $this->db->where('id', $id)->get('orders')->row_array();
    }

    /* =========================================================== */
    public function run()
    {
        $only = $this->input->get('only');
        try {
            $this->promo_tests();
            $this->cashback_tests();
            $this->return_tests();
            $this->routing_tests();
            $this->seller_tests();
            $this->admin_tests();
            $this->integrity_tests();
            $this->decline_tests();
        } catch (Throwable $e) {
            echo "\nEXCEPTION: " . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
            $this->fail++;
            $this->fails[] = 'uncaught exception: ' . $e->getMessage();
        }
        $this->cleanup();
        $this->summary();
    }

    /* =================== PROMO / OFFER TESTS =================== */
    private function promo_tests()
    {
        $u = $this->mk_user(0, 'zt_promo');

        $this->section('P1  discount calculation');
        $p = $this->mk_promo(['discount' => 10, 'discount_type' => 'percentage', 'max_discount_amount' => 200, 'minimum_order_amount' => 500]);
        $r = validate_promo_code($this->code($p), $u, 1000);
        $this->eq('P1.1 percentage 10% of 1000 = 100', 100.0, $r['data'][0]['final_discount']);
        $this->eq('P1.2 final_total = 900', 900.0, $r['data'][0]['final_total']);
        $this->eq('P1.3 checkout_discount = 100', 100.0, $r['data'][0]['checkout_discount']);

        $r = validate_promo_code($this->code($p), $u, 5000);
        $this->eq('P1.4 percentage capped at max_discount_amount 200', 200.0, $r['data'][0]['final_discount']);
        $this->eq('P1.5 final_total = 4800', 4800.0, $r['data'][0]['final_total']);

        $p2 = $this->mk_promo(['discount' => 150, 'discount_type' => 'amount', 'max_discount_amount' => 500, 'minimum_order_amount' => 100]);
        $r = validate_promo_code($this->code($p2), $u, 1000);
        $this->eq('P1.6 flat amount 150', 150.0, $r['data'][0]['final_discount']);
        $this->eq('P1.7 final_total = 850', 850.0, $r['data'][0]['final_total']);

        $p3 = $this->mk_promo(['discount' => 5000, 'discount_type' => 'amount', 'max_discount_amount' => 9999, 'minimum_order_amount' => 100]);
        $r = validate_promo_code($this->code($p3), $u, 1000);
        $this->eq('P1.8 flat amount larger than cart clamped to cart', 1000.0, $r['data'][0]['final_discount']);
        $this->eq('P1.9 final_total never negative', 0.0, $r['data'][0]['final_total']);

        $this->section('P2  minimum order amount');
        $p4 = $this->mk_promo(['minimum_order_amount' => 499.50]);
        $r = validate_promo_code($this->code($p4), $u, 499.00);
        $this->ok('P2.1 below fractional minimum rejected', !empty($r['error']), json_encode($r['message'] ?? ''));
        $r = validate_promo_code($this->code($p4), $u, 499.50);
        $this->ok('P2.2 exactly at minimum accepted', empty($r['error']), json_encode($r['message'] ?? ''));
        $r = validate_promo_code($this->code($p4), $u, 600);
        $this->ok('P2.3 above minimum accepted', empty($r['error']), json_encode($r['message'] ?? ''));

        $this->section('P3  validity window and status');
        $pexp = $this->mk_promo(['start_date' => date('Y-m-d', strtotime('-30 days')), 'end_date' => date('Y-m-d', strtotime('-1 day'))]);
        $r = validate_promo_code($this->code($pexp), $u, 1000);
        $this->ok('P3.1 expired code rejected', !empty($r['error']), json_encode($r['message'] ?? ''));

        $pfut = $this->mk_promo(['start_date' => date('Y-m-d', strtotime('+1 day')), 'end_date' => date('Y-m-d', strtotime('+30 days'))]);
        $r = validate_promo_code($this->code($pfut), $u, 1000);
        $this->ok('P3.2 not-yet-started code rejected', !empty($r['error']), json_encode($r['message'] ?? ''));

        $ptoday = $this->mk_promo(['start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d')]);
        $r = validate_promo_code($this->code($ptoday), $u, 1000);
        $this->ok('P3.3 code valid on its last day (inclusive)', empty($r['error']), json_encode($r['message'] ?? ''));

        $poff = $this->mk_promo(['status' => 0]);
        $r = validate_promo_code($this->code($poff), $u, 1000);
        $this->ok('P3.4 disabled code rejected', !empty($r['error']), json_encode($r['message'] ?? ''));

        $r = validate_promo_code('ZT_DOES_NOT_EXIST', $u, 1000);
        $this->ok('P3.5 unknown code rejected', !empty($r['error']), json_encode($r['message'] ?? ''));

        $this->section('P4  usage limits (single use)');
        $psingle = $this->mk_promo(['repeat_usage' => 0, 'no_of_repeat_usage' => 0, 'minimum_order_amount' => 100]);
        $csingle = $this->code($psingle);
        $r = validate_promo_code($csingle, $u, 1000);
        $this->ok('P4.1 first redemption allowed', empty($r['error']), json_encode($r['message'] ?? ''));

        list($oid1, $iid1) = $this->mk_order($u, [['variant_id' => 0, 'seller_id' => 0, 'qty' => 1, 'price' => 1000]], ['promo_code' => $csingle, 'promo_discount' => 100]);
        $r = validate_promo_code($csingle, $u, 1000);
        $this->ok('P4.2 second redemption by same user blocked', !empty($r['error']), json_encode($r['message'] ?? ''));

        $u2 = $this->mk_user(0, 'zt_promo2');
        $r = validate_promo_code($csingle, $u2, 1000);
        $this->ok('P4.3 different user may still redeem', empty($r['error']), json_encode($r['message'] ?? ''));

        // cancel the order -> quota should be released
        $this->db->where('order_id', $oid1)->update('order_items', ['active_status' => 'cancelled']);
        $r = validate_promo_code($csingle, $u, 1000);
        $this->ok('P4.4 cancelled order releases the users own quota', empty($r['error']), json_encode($r['message'] ?? ''));
        $this->db->where('order_id', $oid1)->update('order_items', ['active_status' => 'received']);

        $this->section('P5  usage limits (repeat usage)');
        $prep = $this->mk_promo(['repeat_usage' => 1, 'no_of_repeat_usage' => 2, 'minimum_order_amount' => 100]);
        $crep = $this->code($prep);
        $u3 = $this->mk_user(0, 'zt_promo3');
        $r = validate_promo_code($crep, $u3, 1000);
        $this->ok('P5.1 use 1 of 2 allowed', empty($r['error']), json_encode($r['message'] ?? ''));
        $this->mk_order($u3, [['variant_id' => 0, 'seller_id' => 0, 'qty' => 1, 'price' => 1000]], ['promo_code' => $crep, 'promo_discount' => 100]);
        $r = validate_promo_code($crep, $u3, 1000);
        $this->ok('P5.2 use 2 of 2 allowed', empty($r['error']), json_encode($r['message'] ?? ''));
        $this->mk_order($u3, [['variant_id' => 0, 'seller_id' => 0, 'qty' => 1, 'price' => 1000]], ['promo_code' => $crep, 'promo_discount' => 100]);
        $r = validate_promo_code($crep, $u3, 1000);
        $this->ok('P5.3 use 3 of 2 blocked', !empty($r['error']), json_encode($r['message'] ?? ''));
        $this->ok('P5.4 blocked message mentions usage limit', isset($r['message']) && stripos($r['message'], 'usage limit') !== false, json_encode($r['message'] ?? ''));

        $this->section('P6  no_of_users cap (distinct customers)');
        $pcap = $this->mk_promo(['no_of_users' => 2, 'repeat_usage' => 1, 'no_of_repeat_usage' => 5, 'minimum_order_amount' => 100]);
        $ccap = $this->code($pcap);
        $ua = $this->mk_user(0, 'zt_a');
        $ub = $this->mk_user(0, 'zt_b');
        $uc = $this->mk_user(0, 'zt_c');
        $this->mk_order($ua, [['variant_id' => 0, 'seller_id' => 0, 'qty' => 1, 'price' => 1000]], ['promo_code' => $ccap, 'promo_discount' => 100]);
        $this->mk_order($ua, [['variant_id' => 0, 'seller_id' => 0, 'qty' => 1, 'price' => 1000]], ['promo_code' => $ccap, 'promo_discount' => 100]);
        $r = validate_promo_code($ccap, $ub, 1000);
        $this->ok('P6.1 two orders by one user counts as ONE user (2nd user allowed)', empty($r['error']), json_encode($r['message'] ?? ''));
        $this->mk_order($ub, [['variant_id' => 0, 'seller_id' => 0, 'qty' => 1, 'price' => 1000]], ['promo_code' => $ccap, 'promo_discount' => 100]);
        $r = validate_promo_code($ccap, $uc, 1000);
        $this->ok('P6.2 third distinct user blocked by no_of_users = 2', !empty($r['error']), json_encode($r['message'] ?? ''));
        $r = validate_promo_code($ccap, $ua, 1000);
        $this->ok('P6.3 existing user still allowed after cap reached', empty($r['error']), json_encode($r['message'] ?? ''));

        $this->section('P7  cashback codes');
        $pcb = $this->mk_promo(['is_cashback' => 1, 'discount' => 10, 'discount_type' => 'percentage', 'max_discount_amount' => 500, 'minimum_order_amount' => 100]);
        $r = validate_promo_code($this->code($pcb), $u, 1000);
        $this->eq('P7.1 cashback final_discount reported', 100.0, $r['data'][0]['final_discount']);
        $this->eq('P7.2 cashback does NOT reduce checkout amount', 0.0, $r['data'][0]['checkout_discount']);
        $this->eq('P7.3 cashback final_total unchanged', 1000.0, $r['data'][0]['final_total']);

        $this->section('P8  recalculation mode (skip_usage_checks)');
        $pr = $this->mk_promo(['repeat_usage' => 0, 'no_of_repeat_usage' => 0, 'minimum_order_amount' => 500, 'discount' => 10, 'discount_type' => 'percentage', 'max_discount_amount' => 1000]);
        $cpr = $this->code($pr);
        $this->mk_order($u, [['variant_id' => 0, 'seller_id' => 0, 'qty' => 1, 'price' => 2000]], ['promo_code' => $cpr, 'promo_discount' => 200]);
        $r = validate_promo_code($cpr, $u, 1000, true);
        $this->ok('P8.1 recalc ignores single-use exhaustion', empty($r['error']), json_encode($r['message'] ?? ''));
        $this->eq('P8.2 recalc resizes discount to remaining cart', 100.0, $r['data'][0]['final_discount'] ?? -1);

        $this->db->where('id', $pr)->update('promo_codes', ['status' => 0, 'end_date' => date('Y-m-d', strtotime('-1 day'))]);
        $r = validate_promo_code($cpr, $u, 1000, true);
        $this->ok('P8.3 recalc ignores campaign switched off / expired', empty($r['error']), json_encode($r['message'] ?? ''));

        $r = validate_promo_code($cpr, $u, 400, true);
        $this->ok('P8.4 recalc still enforces minimum_order_amount', !empty($r['error']), json_encode($r['message'] ?? ''));

        $d = recalculate_promo_discount($cpr, 200, $u, 1000, 'RazorPay', 0, 0);
        $this->eq('P8.5 recalculate_promo_discount resizes 200 -> 100', 100.0, $d);
        $d = recalculate_promo_discount($cpr, 200, $u, 400, 'RazorPay', 0, 0);
        $this->eq('P8.6 recalculate_promo_discount forfeits below minimum', 0.0, $d);
        $d = recalculate_promo_discount('', 200, $u, 1000, 'RazorPay', 0, 0);
        $this->eq('P8.7 no promo code -> unchanged', 200.0, $d);

        $pcb2 = $this->mk_promo(['is_cashback' => 1, 'discount' => 10, 'discount_type' => 'percentage', 'max_discount_amount' => 500, 'minimum_order_amount' => 100]);
        $d = recalculate_promo_discount($this->code($pcb2), 0, $u, 1000, 'RazorPay', 0, 0);
        $this->eq('P8.8 cashback code recalculates to 0 order discount', 0.0, $d);

        $this->section('P9  checkout_discount is what place_order now charges on');
        $cases = [
            ['over' => ['discount' => 20, 'discount_type' => 'percentage', 'max_discount_amount' => 5000, 'minimum_order_amount' => 0], 'total' => 1000, 'expect' => 200.0],
            ['over' => ['discount' => 20, 'discount_type' => 'percentage', 'max_discount_amount' => 50, 'minimum_order_amount' => 0], 'total' => 1000, 'expect' => 50.0],
            ['over' => ['discount' => 300, 'discount_type' => 'amount', 'max_discount_amount' => 5000, 'minimum_order_amount' => 0], 'total' => 1000, 'expect' => 300.0],
            ['over' => ['discount' => 5000, 'discount_type' => 'amount', 'max_discount_amount' => 9999, 'minimum_order_amount' => 0], 'total' => 1000, 'expect' => 1000.0],
            ['over' => ['discount' => 20, 'discount_type' => 'percentage', 'max_discount_amount' => 5000, 'minimum_order_amount' => 0, 'is_cashback' => 1], 'total' => 1000, 'expect' => 0.0],
        ];
        foreach ($cases as $n => $case) {
            $pid = $this->mk_promo($case['over']);
            $r = validate_promo_code($this->code($pid), $u, $case['total']);
            $this->eq('P9.' . ($n + 1) . ' checkout_discount', $case['expect'], $r['data'][0]['checkout_discount']);
            $this->ok('P9.' . ($n + 1) . 'b payable never negative', $case['total'] - floatval($r['data'][0]['checkout_discount']) >= 0);
        }
    }


    /* =================== CASHBACK SETTLEMENT =================== */
    private function cashback_tests()
    {
        $this->section('C  cashback settlement cron');
        $settings = get_settings('system_settings', true);
        $days = isset($settings['max_product_return_days']) ? (int) $settings['max_product_return_days'] : 0;
        $seller = $this->mk_user(0, 'zt_cb_seller');

        $mk = function ($returnable, $days_ago, $label) use ($seller) {
            $cust = $this->mk_user(0, 'zt_cb_' . $label);
            $pc = $this->mk_promo(['is_cashback' => 1, 'discount' => 10, 'discount_type' => 'percentage',
                'max_discount_amount' => 1000, 'minimum_order_amount' => 0, 'repeat_usage' => 0]);
            $code = $this->code($pc);
            list($oid, $iids) = $this->mk_order($cust, [['variant_id' => 0, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000]],
                ['promo_code' => $code, 'promo_discount' => 0, 'payment_method' => 'RazorPay']);
            list($pid, $vid) = $this->mk_product($seller, 1000, 10, $returnable, '1');
            $this->db->where('id', $iids[0])->update('order_items', ['active_status' => 'delivered', 'product_variant_id' => $vid]);
            $this->db->where('id', $oid)->update('orders', ['date_added' => date('Y-m-d H:i:s', strtotime("-$days_ago days"))]);
            return [$cust, $oid, $iids[0]];
        };

        $run = function () {
            ob_start();
            $this->Promo_code_model->settle_cashback_discount();
            return ob_get_clean();
        };

        // non-returnable goods settle the day the order is delivered
        list($cA, $oA) = $mk(0, 0, 'a');
        // returnable goods must wait out the return window
        list($cB, $oB) = $mk(1, 0, 'b');
        // returnable goods whose window closed days ago - a run that was missed must catch up
        list($cC, $oC) = $mk(1, $days + 4, 'c');

        $bA = $this->bal($cA); $bB = $this->bal($cB); $bC = $this->bal($cC);
        $run();
        $this->eq('C1 non-returnable order settles immediately', $bA + 100, $this->bal($cA));
        $this->eq('C2 returnable order still inside its window is NOT settled', $bB, $this->bal($cB));
        $this->eq('C3 returnable order past its window is caught up', $bC + 100, $this->bal($cC));
        $this->ok('C4 batch does not use one order window for all (B held while A/C paid)',
            abs($this->bal($cB) - $bB) < 0.01 && $this->bal($cA) > $bA && $this->bal($cC) > $bC);

        $b2A = $this->bal($cA);
        $run();
        $this->eq('C5 a second run does not pay twice', $b2A, $this->bal($cA));
        $this->eq('C6 held order still held on the second run', $bB, $this->bal($cB));

        $oArow = $this->order($oA);
        $this->eq('C7 settled order records the cashback as its promo_discount', 100.0, $oArow['promo_discount']);
        $txn = $this->db->where(['user_id' => $cA, 'type' => 'credit'])->get('transactions')->num_rows();
        $this->eq('C8 exactly one ledger row for the cashback', 1, $txn);
    }

    /* =================== REFUND ROUTING (GATEWAY vs WALLET) =================== */

    /** Swap the Razorpay library for a stub, so no real money moves during the tests. */
    private function stub_gateway($behaviour)
    {
        $this->load->library('razorpay');           // makes class_exists() true...
        $this->razorpay = new Ztest_fake_razorpay($behaviour); // ...so this override sticks
    }

    private function mk_gateway_payment($order_id, $user_id, $amount, $txn_id = null, $type = 'razorpay')
    {
        return $this->ins('transactions', [
            'transaction_type' => 'transaction',
            'user_id'          => $user_id,
            'order_id'         => $order_id,
            'order_item_id'    => null,
            'type'             => $type,
            'txn_id'           => $txn_id !== null ? $txn_id : ('pay_ZT' . strtoupper(substr(uniqid(), -8))),
            'amount'           => $amount,
            'status'           => 'success',
            'message'          => 'order placed successfully',
        ]);
    }

    private function gateway_refund_rows($order_id)
    {
        return $this->db->where([
            'order_id' => $order_id, 'transaction_type' => 'transaction', 'type' => 'refund',
        ])->get('transactions')->result_array();
    }

    private function routing_tests()
    {
        $seller = $this->mk_user(0, 'zt_g_seller');

        $this->section('G1  a wallet-paid order is refunded to the wallet');
        $c1 = $this->mk_user(0, 'zt_g1');
        list($p1, $v1) = $this->mk_product($seller, 1000, 10, 1, '1');
        list($o1, $i1) = $this->mk_order($c1, [['variant_id' => $v1, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000]], ['payment_method' => 'wallet']);
        $this->mk_gateway_payment($o1, $c1, 1000, '', 'wallet');
        $b1 = $this->bal($c1);
        $this->stub_gateway('success');
        process_refund($i1[0], 'returned', 'order_items');
        $this->eq('G1.1 wallet credited the full amount', $b1 + 1000, $this->bal($c1));
        $this->eq('G1.2 refund_mode is wallet', 'wallet', $this->item($i1[0])['refund_mode']);
        $this->eq('G1.3 no gateway refund attempted', 0, count($this->gateway_refund_rows($o1)));

        $this->section('G2  a card-paid order is refunded to the card');
        $c2 = $this->mk_user(0, 'zt_g2');
        list($p2, $v2) = $this->mk_product($seller, 1000, 10, 1, '1');
        list($o2, $i2) = $this->mk_order($c2, [['variant_id' => $v2, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000]], ['payment_method' => 'RazorPay']);
        $this->mk_gateway_payment($o2, $c2, 1000);
        $b2 = $this->bal($c2);
        $this->stub_gateway('success');
        process_refund($i2[0], 'returned', 'order_items');
        $this->eq('G2.1 wallet NOT credited', $b2, $this->bal($c2));
        $this->eq('G2.2 refund_mode is gateway', 'gateway', $this->item($i2[0])['refund_mode']);
        $rows = $this->gateway_refund_rows($o2);
        $this->eq('G2.3 one gateway refund ledger row', 1, count($rows));
        $this->eq('G2.4 ledger row carries the full amount', 1000.0, $rows[0]['amount']);
        $this->ok('G2.5 ledger row carries the gateway refund id', !empty($rows[0]['txn_id']) && strpos($rows[0]['txn_id'], 'rfnd_') === 0, json_encode($rows[0]['txn_id']));
        $this->eq('G2.6 refund_amount recorded on the item', 1000.0, $this->item($i2[0])['refund_amount']);

        $this->section('G3  gateway refuses: the customer is still made whole');
        $c3 = $this->mk_user(0, 'zt_g3');
        list($p3, $v3) = $this->mk_product($seller, 800, 10, 1, '1');
        list($o3, $i3) = $this->mk_order($c3, [['variant_id' => $v3, 'seller_id' => $seller, 'qty' => 1, 'price' => 800]], ['payment_method' => 'RazorPay']);
        $this->mk_gateway_payment($o3, $c3, 800);
        $b3 = $this->bal($c3);
        $this->stub_gateway('fail');
        process_refund($i3[0], 'returned', 'order_items');
        $this->eq('G3.1 wallet credited the full amount as a fallback', $b3 + 800, $this->bal($c3));
        $this->eq('G3.2 refund_mode falls back to wallet', 'wallet', $this->item($i3[0])['refund_mode']);
        $this->eq('G3.3 no gateway refund ledger row was written', 0, count($this->gateway_refund_rows($o3)));
        $wallet_row = $this->db->where(['user_id' => $c3, 'order_item_id' => $i3[0]])->order_by('id', 'DESC')->get('transactions')->row_array();
        $this->ok('G3.4 the wallet row records why the gateway was not used', stripos($wallet_row['message'], 'gateway refund unavailable') !== false, json_encode($wallet_row['message']));

        $this->section('G4  part wallet, part card: split gateway-first');
        $c4 = $this->mk_user(0, 'zt_g4');
        list($p4, $v4) = $this->mk_product($seller, 1000, 10, 1, '1');
        list($o4, $i4) = $this->mk_order($c4, [['variant_id' => $v4, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000]], ['payment_method' => 'RazorPay']);
        $this->mk_gateway_payment($o4, $c4, 600); // only 600 ever reached the gateway
        $b4 = $this->bal($c4);
        $this->stub_gateway('success');
        process_refund($i4[0], 'returned', 'order_items');
        $this->eq('G4.1 wallet gets only the part the card did not cover', $b4 + 400, $this->bal($c4));
        $this->eq('G4.2 refund_mode is gateway+wallet', 'gateway+wallet', $this->item($i4[0])['refund_mode']);
        $rows = $this->gateway_refund_rows($o4);
        $this->eq('G4.3 the gateway leg is capped at what was captured', 600.0, $rows[0]['amount']);

        $this->section('G5  the card is never refunded more than it was charged');
        $c5 = $this->mk_user(0, 'zt_g5');
        list($p5, $v5) = $this->mk_product($seller, 500, 10, 1, '1');
        list($o5, $i5) = $this->mk_order($c5, [
            ['variant_id' => $v5, 'seller_id' => $seller, 'qty' => 1, 'price' => 500],
            ['variant_id' => $v5, 'seller_id' => $seller, 'qty' => 1, 'price' => 500],
        ], ['payment_method' => 'RazorPay']);
        $this->mk_gateway_payment($o5, $c5, 500); // only one line's worth was actually captured
        $b5 = $this->bal($c5);
        $this->stub_gateway('success');
        process_refund($i5[0], 'returned', 'order_items');
        $this->eq('G5.1 first line goes to the card', 'gateway', $this->item($i5[0])['refund_mode']);
        $this->eq('G5.2 wallet untouched so far', $b5, $this->bal($c5));
        process_refund($i5[1], 'returned', 'order_items');
        $this->eq('G5.3 second line finds no card capacity left', 'wallet', $this->item($i5[1])['refund_mode']);
        $rows = $this->gateway_refund_rows($o5);
        $this->eq('G5.4 exactly one gateway refund on the order', 1, count($rows));
        $total_gateway = 0;
        foreach ($rows as $r) {
            $total_gateway += (float) $r['amount'];
        }
        $this->ok('G5.5 total refunded to the card never exceeds what was captured', $total_gateway <= 500.01, 'gateway total=' . $total_gateway);

        $this->section('G6  COD is refunded to the wallet');
        $c6 = $this->mk_user(0, 'zt_g6');
        list($p6, $v6) = $this->mk_product($seller, 900, 10, 1, '1');
        list($o6, $i6) = $this->mk_order($c6, [['variant_id' => $v6, 'seller_id' => $seller, 'qty' => 1, 'price' => 900]], ['payment_method' => 'COD', 'wallet_balance' => 300]);
        $this->mk_gateway_payment($o6, $c6, 900, '', 'cod');
        $b6 = $this->bal($c6);
        $this->stub_gateway('success');
        process_refund($i6[0], 'returned', 'order_items');
        $this->eq('G6.1 only the wallet-funded part comes back', $b6 + 300, $this->bal($c6));
        $this->eq('G6.2 refund_mode is wallet', 'wallet', $this->item($i6[0])['refund_mode']);
        $this->eq('G6.3 nothing sent to a gateway', 0, count($this->gateway_refund_rows($o6)));

        $this->section('G7  whole-order refund routes the same way');
        $c7 = $this->mk_user(0, 'zt_g7');
        list($p7, $v7) = $this->mk_product($seller, 700, 10, 1, '1');
        list($o7, $i7) = $this->mk_order($c7, [
            ['variant_id' => $v7, 'seller_id' => $seller, 'qty' => 1, 'price' => 700],
            ['variant_id' => $v7, 'seller_id' => $seller, 'qty' => 1, 'price' => 700],
        ], ['payment_method' => 'RazorPay']);
        $this->mk_gateway_payment($o7, $c7, 1400);
        $b7 = $this->bal($c7);
        $this->stub_gateway('success');
        process_refund($o7, 'cancelled', 'orders');
        $this->eq('G7.1 wallet not credited', $b7, $this->bal($c7));
        $rows = $this->gateway_refund_rows($o7);
        $this->eq('G7.2 one gateway refund for the whole order', 1, count($rows));
        $this->eq('G7.3 the whole order total went back to the card', 1400.0, $rows[0]['amount']);
        $this->eq('G7.4 every item records the gateway mode', 'gateway', $this->item($i7[0])['refund_mode']);
        $this->eq('G7.5 second item too', 'gateway', $this->item($i7[1])['refund_mode']);

        $this->section('G8  a second refund attempt cannot pay twice');
        $again = process_refund($i7[0], 'returned', 'order_items');
        $this->ok('G8.1 per-item refund after the order refund short-circuits', !empty($again['already_refunded']), json_encode($again));
        $this->eq('G8.2 still only one gateway refund', 1, count($this->gateway_refund_rows($o7)));
        $this->eq('G8.3 wallet still untouched', $b7, $this->bal($c7));

        // What the admin "refund to card" button checks before it will fire.
        $it = $this->item($i2[0]);
        $blocked = !empty($it['refunded_at']) && (float) $it['refund_amount'] > 0;
        $this->ok('G8.4 an already gateway-refunded item blocks the manual refund button', $blocked, json_encode($it['refund_mode']));

        $this->section('G9  an unrefundable gateway falls back to the wallet');
        $c9 = $this->mk_user(0, 'zt_g9');
        list($p9, $v9) = $this->mk_product($seller, 400, 10, 1, '1');
        list($o9, $i9) = $this->mk_order($c9, [['variant_id' => $v9, 'seller_id' => $seller, 'qty' => 1, 'price' => 400]], ['payment_method' => 'Stripe']);
        $this->mk_gateway_payment($o9, $c9, 400, 'ch_ZT123', 'stripe');
        $b9 = $this->bal($c9);
        $this->stub_gateway('success');
        process_refund($i9[0], 'returned', 'order_items');
        $this->eq('G9.1 a gateway with no refund support pays to the wallet', $b9 + 400, $this->bal($c9));
        $this->eq('G9.2 refund_mode is wallet', 'wallet', $this->item($i9[0])['refund_mode']);

        $this->section('G10  refund_to_payment_source direct cases');
        $c10 = $this->mk_user(0, 'zt_g10');
        list($o10, $i10) = $this->mk_order($c10, [['variant_id' => 0, 'seller_id' => $seller, 'qty' => 1, 'price' => 100]]);
        $r = refund_to_payment_source($o10, $i10[0], $c10, 0, 'zero');
        $this->eq('G10.1 a zero refund does nothing', 'none', $r['mode']);
        $this->eq('G10.2 nothing credited', 0.0, $r['wallet_amount']);
        $r = refund_to_payment_source($o10, $i10[0], $c10, -50, 'negative');
        $this->eq('G10.3 a negative refund does nothing', 'none', $r['mode']);
    }

    /* =================== SELLER RETURN VISIBILITY =================== */
    private function seller_tests()
    {
        $this->section('S1  the seller list only ever shows that seller');
        $sellerA = $this->mk_user(0, 'zt_sr_a');
        $sellerB = $this->mk_user(0, 'zt_sr_b');
        $cust = $this->mk_user(0, 'zt_sr_cust');

        list($pA, $vA) = $this->mk_product($sellerA, 1000, 20, 1, '1');
        list($pB, $vB) = $this->mk_product($sellerB, 1000, 20, 1, '1');
        list($oid, $iids) = $this->mk_order($cust, [
            ['variant_id' => $vA, 'seller_id' => $sellerA, 'qty' => 1, 'price' => 1000],
            ['variant_id' => $vB, 'seller_id' => $sellerB, 'qty' => 2, 'price' => 1000],
        ]);

        $this->ins('return_requests', ['user_id' => $cust, 'product_id' => $pA, 'product_variant_id' => $vA, 'order_id' => $oid, 'order_item_id' => $iids[0], 'status' => 0]);
        $this->ins('return_requests', ['user_id' => $cust, 'product_id' => $pB, 'product_variant_id' => $vB, 'order_id' => $oid, 'order_item_id' => $iids[1], 'status' => 1]);

        $_GET = [];
        $listA = $this->Return_request_model->get_seller_return_request_list($sellerA, false);
        $listB = $this->Return_request_model->get_seller_return_request_list($sellerB, false);

        $this->eq('S1.1 seller A sees exactly one request', 1, $listA['total']);
        $this->eq('S1.2 seller B sees exactly one request', 1, $listB['total']);
        $this->eq('S1.3 seller A sees their own order item', $iids[0], $listA['rows'][0]['order_item_id']);
        $this->eq('S1.4 seller B sees their own order item', $iids[1], $listB['rows'][0]['order_item_id']);
        $this->ok('S1.5 row count matches the reported total', count($listA['rows']) === $listA['total']);

        $this->section('S2  status filter and search stay scoped');
        $_GET = ['status' => '0'];
        $pending = $this->Return_request_model->get_seller_return_request_list($sellerA, false);
        $this->eq('S2.1 pending filter finds seller A request', 1, $pending['total']);
        $_GET = ['status' => '1'];
        $approved = $this->Return_request_model->get_seller_return_request_list($sellerA, false);
        $this->eq('S2.2 seller A has no approved requests', 0, $approved['total']);
        $_GET = ['status' => '1'];
        $approvedB = $this->Return_request_model->get_seller_return_request_list($sellerB, false);
        $this->eq('S2.3 seller B does have one', 1, $approvedB['total']);

        // Searching for the OTHER seller's order item must not leak it.
        $_GET = ['search' => (string) $iids[1]];
        $leak = $this->Return_request_model->get_seller_return_request_list($sellerA, false);
        $this->eq('S2.4 searching for another seller item returns nothing', 0, $leak['total']);

        $_GET = ['sort' => 'id; DROP TABLE users--', 'order' => 'desc'];
        $safe = $this->Return_request_model->get_seller_return_request_list($sellerA, false);
        $this->ok('S2.5 an unknown sort column falls back safely', is_array($safe) && $safe['total'] === 1);
        $this->ok('S2.6 users table still exists', $this->db->table_exists('users'));
        $_GET = [];

        $this->section('S3  summary counts');
        $summary = $this->Return_request_model->get_seller_return_summary($sellerA);
        $this->eq('S3.1 seller A pending', 1, $summary['pending']);
        $this->eq('S3.2 seller A approved', 0, $summary['approved']);
        $this->eq('S3.3 seller A total', 1, $summary['total']);
        $summaryB = $this->Return_request_model->get_seller_return_summary($sellerB);
        $this->eq('S3.4 seller B approved', 1, $summaryB['approved']);

        $this->section('S4  the refund column reflects how the customer was paid');
        // approve seller A request -> refund routed and reported
        $rrA = $this->db->where(['order_item_id' => $iids[0]])->get('return_requests')->row_array();
        $this->mk_gateway_payment($oid, $cust, 1000);
        $this->stub_gateway('success');
        $this->Return_request_model->update_return_request([
            'return_request_id' => $rrA['id'], 'status' => '1', 'order_item_id' => $iids[0],
        ]);
        $_GET = [];
        $listA = $this->Return_request_model->get_seller_return_request_list($sellerA, false);
        $this->ok('S4.1 the refund column names the original payment method',
            stripos($listA['rows'][0]['refund'], 'Original payment method') !== false, json_encode($listA['rows'][0]['refund']));
        $this->ok('S4.2 the request now reads as approved',
            stripos($listA['rows'][0]['status'], 'Approved') !== false, json_encode($listA['rows'][0]['status']));

        $this->section('S5  an unrefunded request says so');
        $listB = $this->Return_request_model->get_seller_return_request_list($sellerB, false);
        $this->ok('S5.1 seller B item is not refunded yet',
            stripos($listB['rows'][0]['refund'], 'Not refunded yet') !== false, json_encode($listB['rows'][0]['refund']));

        $this->section('S6  seller-authored returns are refused');
        // The seller web screen and the seller app API both reject a posted status of
        // 'returned' before anything is written. Assert the guard exists in both entry points -
        // the money paths behind them are covered by R1-R16 and G1-G10.
        $web = file_get_contents(APPPATH . 'controllers/seller/Orders.php');
        $api = file_get_contents(APPPATH . 'controllers/seller/app/v1/Api.php');
        $view = file_get_contents(APPPATH . 'views/seller/pages/forms/edit-orders.php');
        $this->ok('S6.1 the seller web endpoint refuses a returned status',
            strpos($web, "trim(\$_POST['status']) == 'returned'") !== false
            && strpos($web, 'You cannot mark an item returned from here.') !== false);
        $this->ok('S6.2 the seller app API refuses a returned status',
            strpos($api, "trim(\$_POST['status']) == 'returned'") !== false
            && strpos($api, 'You cannot mark an item returned from here.') !== false);
        $this->ok('S6.3 the seller status dropdown no longer offers Returned',
            strpos($view, '<option value="returned">') === false);
        $this->ok('S6.4 the seller can still see the returned badge',
            strpos($view, '"returned" => "danger"') !== false);
    }

    /* =================== ADMIN RETURN LIST =================== */
    private function admin_tests()
    {
        $this->section('A1  the admin list reports the seller and the refund');
        $seller = $this->mk_user(0, 'zt_ad_seller');
        $cust = $this->mk_user(0, 'zt_ad_cust');
        list($pid, $vid) = $this->mk_product($seller, 1200, 20, 1, '1');
        list($oid, $iids) = $this->mk_order($cust, [['variant_id' => $vid, 'seller_id' => $seller, 'qty' => 1, 'price' => 1200]], ['payment_method' => 'RazorPay']);
        $this->mk_gateway_payment($oid, $cust, 1200);
        $rr = $this->ins('return_requests', [
            'user_id' => $cust, 'product_id' => $pid, 'product_variant_id' => $vid,
            'order_id' => $oid, 'order_item_id' => $iids[0], 'status' => 0,
        ]);

        $_GET = ['search' => (string) $iids[0]];
        ob_start();
        $this->Return_request_model->get_return_request_list();
        $payload = json_decode(ob_get_clean(), true);
        $_GET = [];

        $this->ok('A1.1 the request is listed', !empty($payload['rows']), json_encode($payload['total'] ?? null));
        $row = null;
        foreach ($payload['rows'] as $r) {
            if ((string) $r['order_item_id'] === (string) $iids[0]) {
                $row = $r;
            }
        }
        $this->ok('A1.2 the seeded request is in the payload', $row !== null);
        if ($row !== null) {
            $this->ok('A1.3 the seller is named', !empty($row['seller_name']), json_encode($row['seller_name']));
            $this->ok('A1.4 an undecided request shows as not refunded',
                stripos($row['refund'], 'Not refunded') !== false, json_encode($row['refund']));
            $this->ok('A1.5 the count matches the rows returned',
                (int) $payload['total'] === count($payload['rows']), 'total=' . $payload['total'] . ' rows=' . count($payload['rows']));
        }

        $this->section('A2  after approval the list shows where the money went');
        $this->stub_gateway('success');
        $res = $this->Return_request_model->update_return_request([
            'return_request_id' => $rr, 'status' => '1', 'order_item_id' => $iids[0],
        ]);
        $this->ok('A2.1 approval succeeded', empty($res['error']), json_encode($res));

        $_GET = ['search' => (string) $iids[0]];
        ob_start();
        $this->Return_request_model->get_return_request_list();
        $payload = json_decode(ob_get_clean(), true);
        $_GET = [];

        $row = null;
        foreach ($payload['rows'] as $r) {
            if ((string) $r['order_item_id'] === (string) $iids[0]) {
                $row = $r;
            }
        }
        $this->ok('A2.2 the approved request is still listed', $row !== null);
        if ($row !== null) {
            $this->ok('A2.3 the refund column names the original payment method',
                stripos($row['refund'], 'Original payment method') !== false, json_encode($row['refund']));
            $this->ok('A2.4 the refunded amount is shown',
                strpos($row['refund'], '1,200.00') !== false, json_encode($row['refund']));
            $this->ok('A2.5 the request reads as approved',
                stripos($row['status'], 'Approved') !== false, json_encode($row['status']));
        }
        $this->eq('A2.6 the customer was paid at the gateway, not the wallet', 'gateway', $this->item($iids[0])['refund_mode']);
    }

    /* ============ ACCOUNTING GUARD, CLAIMS, QUOTA RELEASE ============ */
    private function integrity_tests()
    {
        $this->section('X1  a manual card refund still leaves the books to be adjusted');
        $seller = $this->mk_user(0, 'zt_x_seller');
        $cust = $this->mk_user(0, 'zt_x_cust');
        list($pid, $vid) = $this->mk_product($seller, 1000, 20, 1, '1');
        list($oid, $iids) = $this->mk_order($cust, [
            ['variant_id' => $vid, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000],
            ['variant_id' => $vid, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000],
        ], ['payment_method' => 'RazorPay']);

        // The item was delivered and settled: the seller has already been paid.
        $this->db->where('id', $iids[0])->update('order_items', [
            'admin_commission_amount' => 100, 'seller_commission_amount' => 900,
            'commission_rate' => 10, 'is_credited' => 1, 'active_status' => 'delivered',
        ]);
        $this->ins('seller_settlements', [
            'seller_id' => $seller, 'order_id' => $oid, 'order_item_id' => $iids[0],
            'order_amount' => 1000, 'commission_percent' => 10, 'commission_amount' => 100,
            'net_payable' => 900, 'settlement_status' => 'settled',
            'taxable_value' => 1000, 'product_tax_amount' => 0, 'commission_gst_amount' => 0,
            'tcs_amount' => 0, 'tds_amount' => 0, 'shipping_deduction' => 0, 'gateway_fee' => 0,
        ]);
        $this->db->set('balance', 900)->where('id', $seller)->update('users');

        // An admin refunds the card by hand: the customer is paid, the books are untouched.
        $this->db->where('id', $iids[0])->update('order_items', [
            'refunded_at' => date('Y-m-d H:i:s'), 'refund_amount' => 1000, 'refund_mode' => 'gateway',
        ]);

        $seller_bal = $this->bal($seller);
        $cust_bal = $this->bal($cust);
        $order_total_before = $this->order($oid)['total'];

        // ...and now the return is approved.
        $this->stub_gateway('success');
        $res = process_refund($iids[0], 'returned', 'order_items');
        $this->ok('X1.1 the refund call succeeds', empty($res['error']), json_encode($res));
        $this->eq('X1.2 the customer is NOT paid a second time', $cust_bal, $this->bal($cust));
        $this->eq('X1.3 the recorded refund is untouched', 1000.0, $this->item($iids[0])['refund_amount']);
        $this->eq('X1.4 the refund channel is untouched', 'gateway', $this->item($iids[0])['refund_mode']);
        $this->eq('X1.5 the seller commission IS now clawed back', $seller_bal - 900, $this->bal($seller));
        $this->eq('X1.6 the settlement is marked reversed', 'reversed',
            $this->db->where('order_item_id', $iids[0])->get('seller_settlements')->row()->settlement_status);
        $this->eq('X1.7 commission amounts zeroed', 0.0, $this->item($iids[0])['admin_commission_amount']);
        $this->eq('X1.8 the order total now excludes the returned line', $order_total_before - 1000, $this->order($oid)['total']);
        $this->ok('X1.9 the item is stamped as accounted', !empty($this->item($iids[0])['accounted_at']));

        $this->section('X2  the accounting still only ever runs once');
        $seller_bal2 = $this->bal($seller);
        $order_total2 = $this->order($oid)['total'];
        $again = process_refund($iids[0], 'returned', 'order_items');
        $this->ok('X2.1 a repeat call short-circuits', !empty($again['already_refunded']), json_encode($again));
        $this->eq('X2.2 the seller is not debited twice', $seller_bal2, $this->bal($seller));
        $this->eq('X2.3 the order total is not reduced twice', $order_total2, $this->order($oid)['total']);

        $this->section('X3  the normal path stamps both guards at once');
        $cust3 = $this->mk_user(0, 'zt_x3');
        list($p3, $v3) = $this->mk_product($seller, 500, 20, 1, '1');
        list($o3, $i3) = $this->mk_order($cust3, [['variant_id' => $v3, 'seller_id' => $seller, 'qty' => 1, 'price' => 500]]);
        process_refund($i3[0], 'cancelled', 'order_items');
        $it3 = $this->item($i3[0]);
        $this->ok('X3.1 refunded_at set', !empty($it3['refunded_at']));
        $this->ok('X3.2 accounted_at set', !empty($it3['accounted_at']));

        $this->section('X4  approving a return is claimed atomically');
        $cust4 = $this->mk_user(0, 'zt_x4');
        list($p4, $v4) = $this->mk_product($seller, 700, 20, 1, '1');
        list($o4, $i4) = $this->mk_order($cust4, [['variant_id' => $v4, 'seller_id' => $seller, 'qty' => 1, 'price' => 700]]);
        $rr = $this->ins('return_requests', [
            'user_id' => $cust4, 'product_id' => $p4, 'product_variant_id' => $v4,
            'order_id' => $o4, 'order_item_id' => $i4[0], 'status' => 0,
        ]);
        $b4 = $this->bal($cust4);
        $s4 = $this->stock($v4);
        $this->stub_gateway('success');

        $first = $this->Return_request_model->update_return_request([
            'return_request_id' => $rr, 'status' => '1', 'order_item_id' => $i4[0],
        ]);
        $second = $this->Return_request_model->update_return_request([
            'return_request_id' => $rr, 'status' => '1', 'order_item_id' => $i4[0],
        ]);
        $this->ok('X4.1 the first approval wins', empty($first['error']), json_encode($first));
        $this->ok('X4.2 the second is refused by the claim', !empty($second['error']), json_encode($second));
        $this->eq('X4.3 the customer is paid exactly once', $b4 + 700, $this->bal($cust4));
        $this->eq('X4.4 the stock comes back exactly once', $s4 + 1, $this->stock($v4));
        $txns = $this->db->where(['order_item_id' => $i4[0], 'user_id' => $cust4])->get('transactions')->num_rows();
        $this->eq('X4.5 one ledger row', 1, $txns);

        $this->section('X5  rejecting after approval is refused too');
        $reject = $this->Return_request_model->update_return_request([
            'return_request_id' => $rr, 'status' => '2', 'order_item_id' => $i4[0],
        ]);
        $this->ok('X5.1 cannot flip an approved request to rejected', !empty($reject['error']), json_encode($reject));
        $this->eq('X5.2 the request is still approved', '1',
            $this->db->where('id', $rr)->get('return_requests')->row()->status);

        $this->section('X6  a failed refund hands the request back to pending');
        $cust6 = $this->mk_user(0, 'zt_x6');
        list($p6, $v6) = $this->mk_product($seller, 600, 20, 1, '1');
        list($o6, $i6) = $this->mk_order($cust6, [['variant_id' => $v6, 'seller_id' => $seller, 'qty' => 1, 'price' => 600]]);
        $rr6 = $this->ins('return_requests', [
            'user_id' => $cust6, 'product_id' => $p6, 'product_variant_id' => $v6,
            'order_id' => $o6, 'order_item_id' => 999999999, 'status' => 0,
        ]);
        $bad = $this->Return_request_model->update_return_request([
            'return_request_id' => $rr6, 'status' => '1', 'order_item_id' => 999999999,
        ]);
        $this->ok('X6.1 the approval reports the failure', !empty($bad['error']), json_encode($bad));
        $this->eq('X6.2 the request is back to pending so it can be retried', '0',
            $this->db->where('id', $rr6)->get('return_requests')->row()->status);

        $this->section('X7  promo quota is released by a returned order');
        $u7 = $this->mk_user(0, 'zt_x7');
        $p7 = $this->mk_promo(['repeat_usage' => 0, 'no_of_repeat_usage' => 0, 'minimum_order_amount' => 100]);
        $c7 = $this->code($p7);
        list($o7, $i7) = $this->mk_order($u7, [['variant_id' => 0, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000]],
            ['promo_code' => $c7, 'promo_discount' => 100]);

        $r = validate_promo_code($c7, $u7, 1000);
        $this->ok('X7.1 the code is spent while the order stands', !empty($r['error']), json_encode($r['message'] ?? ''));

        $this->db->where('order_id', $o7)->update('order_items', ['active_status' => 'returned']);
        $r = validate_promo_code($c7, $u7, 1000);
        $this->ok('X7.2 a fully returned order releases the quota', empty($r['error']), json_encode($r['message'] ?? ''));

        // ...but only when nothing on the order still stands
        list($o7b, $i7b) = $this->mk_order($u7, [
            ['variant_id' => 0, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000],
            ['variant_id' => 0, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000],
        ], ['promo_code' => $c7, 'promo_discount' => 100]);
        $this->db->where('id', $i7b[0])->update('order_items', ['active_status' => 'returned']);
        $r = validate_promo_code($c7, $u7, 1000);
        $this->ok('X7.3 a partly returned order still counts', !empty($r['error']), json_encode($r['message'] ?? ''));

        $this->section('X8  no_of_users quota is released the same way');
        $pcap = $this->mk_promo(['no_of_users' => 1, 'repeat_usage' => 1, 'no_of_repeat_usage' => 5, 'minimum_order_amount' => 100]);
        $ccap = $this->code($pcap);
        $ua = $this->mk_user(0, 'zt_x8a');
        $ub = $this->mk_user(0, 'zt_x8b');
        list($oa, $ia) = $this->mk_order($ua, [['variant_id' => 0, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000]],
            ['promo_code' => $ccap, 'promo_discount' => 100]);
        $r = validate_promo_code($ccap, $ub, 1000);
        $this->ok('X8.1 the single user slot is taken', !empty($r['error']), json_encode($r['message'] ?? ''));
        $this->db->where('order_id', $oa)->update('order_items', ['active_status' => 'returned']);
        $r = validate_promo_code($ccap, $ub, 1000);
        $this->ok('X8.2 returning that order frees the slot', empty($r['error']), json_encode($r['message'] ?? ''));

        $this->section('X9  validate_promo_code never returns NULL');
        $r = validate_promo_code('', $u7, 1000);
        $this->ok('X9.1 an empty code returns an error payload, not null', is_array($r) && !empty($r['error']), json_encode($r));
        $this->ok('X9.2 the payload carries final_total', isset($r['data']['final_total']), json_encode($r));
        $r = validate_promo_code(null, $u7, 1000);
        $this->ok('X9.3 a null code returns an error payload', is_array($r) && !empty($r['error']), json_encode($r));

        $this->section('X10  order-level refund catches up the accounting');
        $cust10 = $this->mk_user(0, 'zt_x10');
        list($p10, $v10) = $this->mk_product($seller, 400, 20, 1, '1');
        list($o10, $i10) = $this->mk_order($cust10, [
            ['variant_id' => $v10, 'seller_id' => $seller, 'qty' => 1, 'price' => 400],
            ['variant_id' => $v10, 'seller_id' => $seller, 'qty' => 1, 'price' => 400],
        ]);
        // line 1 was refunded by hand, books untouched
        $this->db->where('id', $i10[0])->update('order_items', [
            'refunded_at' => date('Y-m-d H:i:s'), 'refund_amount' => 400, 'refund_mode' => 'gateway',
        ]);
        $this->ins('seller_settlements', [
            'seller_id' => $seller, 'order_id' => $o10, 'order_item_id' => $i10[0],
            'order_amount' => 400, 'commission_percent' => 10, 'commission_amount' => 40,
            'net_payable' => 360, 'settlement_status' => 'settled',
            'taxable_value' => 400, 'product_tax_amount' => 0, 'commission_gst_amount' => 0,
            'tcs_amount' => 0, 'tds_amount' => 0, 'shipping_deduction' => 0, 'gateway_fee' => 0,
        ]);
        $b10 = $this->bal($cust10);
        $res10 = process_refund($o10, 'cancelled', 'orders');
        $this->ok('X10.1 the order refund does not pay again over a part-refunded order',
            !empty($res10['already_refunded']), json_encode($res10));
        $this->eq('X10.2 the customer wallet is untouched', $b10, $this->bal($cust10));
        $this->eq('X10.3 the outstanding commission is still reversed', 'reversed',
            $this->db->where('order_item_id', $i10[0])->get('seller_settlements')->row()->settlement_status);
        $this->ok('X10.4 both lines are stamped accounted',
            !empty($this->item($i10[0])['accounted_at']) && !empty($this->item($i10[1])['accounted_at']));
    }

    /* ============ SETTLEMENT AFTER A DECLINED RETURN ============ */
    private function decline_tests()
    {
        $this->section('D1  a refused return leaves the seller payable');
        $seller = $this->mk_user(0, 'zt_d_seller');
        $cust = $this->mk_user(0, 'zt_d_cust');
        list($pid, $vid) = $this->mk_product($seller, 1000, 20, 1, '1');
        list($oid, $iids) = $this->mk_order($cust, [['variant_id' => $vid, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000]]);

        $settings = get_settings('system_settings', true);
        $days = isset($settings['max_product_return_days']) ? (int) $settings['max_product_return_days'] : 0;
        $delivered = date('Y-m-d H:i:s', strtotime('-' . ($days + 3) . ' days'));
        $this->db->where('id', $iids[0])->update('order_items', [
            'active_status' => 'delivered', 'delivered_at' => $delivered, 'is_credited' => 0,
        ]);

        $rr = $this->ins('return_requests', [
            'user_id' => $cust, 'product_id' => $pid, 'product_variant_id' => $vid,
            'order_id' => $oid, 'order_item_id' => $iids[0], 'status' => 0,
        ]);
        $res = $this->Return_request_model->update_return_request([
            'return_request_id' => $rr, 'status' => '2', 'order_item_id' => $iids[0], 'update_remarks' => 'used',
        ]);
        $this->ok('D1.1 the return is rejected', empty($res['error']), json_encode($res));
        $this->eq('D1.2 the item sits at return_request_decline', 'return_request_decline', $this->item($iids[0])['active_status']);
        $this->ok('D1.3 no refund was paid', empty($this->item($iids[0])['refunded_at']));

        // The settlement sweep must still see this item.
        $settleable = $this->db
            ->where("oi.active_status IN ('delivered', 'return_request_decline')", null, false)
            ->where('oi.is_credited', 0)
            ->where('oi.id', $iids[0])
            ->get('order_items oi')->num_rows();
        $this->eq('D1.4 the item is inside the settlement selection', 1, $settleable);

        $this->section('D2  the decline is still a terminal customer state');
        $chk = validate_order_status($iids[0], 'returned', 'order_items', $cust, true);
        $this->ok('D2.1 the customer cannot re-raise past the window', !empty($chk['error']), json_encode($chk['message'] ?? $chk));

        $this->section('D3  staff can still reverse a decline');
        $chk = validate_order_status($iids[0], 'returned', 'order_items', null, false);
        $this->ok('D3.1 a declined item is no longer treated as undelivered',
            empty($chk['error']) || stripos(json_encode($chk['message'] ?? ''), 'not delivered') === false,
            json_encode($chk['message'] ?? $chk));

        $this->section('D4  a pending return is NOT payable');
        $cust4 = $this->mk_user(0, 'zt_d4');
        list($p4, $v4) = $this->mk_product($seller, 800, 20, 1, '1');
        list($o4, $i4) = $this->mk_order($cust4, [['variant_id' => $v4, 'seller_id' => $seller, 'qty' => 1, 'price' => 800]]);
        $this->db->where('id', $i4[0])->update('order_items', ['active_status' => 'return_request_pending']);
        $n = $this->db->where("active_status IN ('delivered', 'return_request_decline')", null, false)
            ->where('id', $i4[0])->get('order_items')->num_rows();
        $this->eq('D4.1 a pending return is outside the settlement selection', 0, $n);

        $this->db->where('id', $i4[0])->update('order_items', ['active_status' => 'return_request_approved']);
        $n = $this->db->where("active_status IN ('delivered', 'return_request_decline')", null, false)
            ->where('id', $i4[0])->get('order_items')->num_rows();
        $this->eq('D4.2 an approved return is outside it too', 0, $n);

        $this->section('D5  stock restore is per line, not per order');
        // The admin/seller/delivery-boy app endpoints all restored stock only when the line
        // being changed happened to be the LAST one on its order. Assert the guard is gone.
        $files = [
            'admin app api' => APPPATH . 'controllers/admin/app/v1/Api.php',
            'seller app api' => APPPATH . 'controllers/seller/app/v1/Api.php',
            'delivery boy' => APPPATH . 'controllers/delivery_boy/Orders.php',
        ];
        foreach ($files as $label => $file) {
            $src = file_get_contents($file);
            $restore_pos = strpos($src, 'restore_order_item_stock');
            $counter_pos = strpos($src, 'order_cancel_counter\']) + 1');
            $this->ok('D5 ' . $label . ' restores before the last-item check',
                $restore_pos !== false && ($counter_pos === false || $restore_pos < $counter_pos),
                'restore@' . var_export($restore_pos, true) . ' counter@' . var_export($counter_pos, true));
        }

        $this->section('D6  a multi-item order restores every cancelled line');
        $cust6 = $this->mk_user(0, 'zt_d6');
        list($p6, $v6) = $this->mk_product($seller, 300, 50, 1, '1');
        list($o6, $i6) = $this->mk_order($cust6, [
            ['variant_id' => $v6, 'seller_id' => $seller, 'qty' => 2, 'price' => 300],
            ['variant_id' => $v6, 'seller_id' => $seller, 'qty' => 3, 'price' => 300],
        ]);
        $s6 = $this->stock($v6);
        restore_order_item_stock($i6[0], 'Order item cancelled');
        $this->eq('D6.1 first line restored on its own', $s6 + 2, $this->stock($v6));
        restore_order_item_stock($i6[1], 'Order item cancelled');
        $this->eq('D6.2 second line restored too', $s6 + 5, $this->stock($v6));
    }

    /* =================== RETURN / REFUND TESTS =================== */
    private function return_tests()
    {
        $this->section('R1  approve a return: refund, stock, status, commission');
        $seller = $this->mk_user(0, 'zt_seller');
        $cust = $this->mk_user(0, 'zt_buyer');
        list($pid, $vid) = $this->mk_product($seller, 1000, 100, 1);
        list($oid, $iids) = $this->mk_order($cust, [
            ['variant_id' => $vid, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000],
            ['variant_id' => $vid, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000],
        ], ['payment_method' => 'RazorPay']);

        $this->db->insert('return_requests', [
            'user_id' => $cust, 'product_id' => $pid, 'product_variant_id' => $vid,
            'order_id' => $oid, 'order_item_id' => $iids[0], 'status' => 0,
        ]);
        $rr = $this->db->insert_id();

        $bal_before = $this->bal($cust);
        $stock_before = $this->stock($vid);

        $res = $this->Return_request_model->update_return_request([
            'return_request_id' => $rr, 'status' => '1', 'order_item_id' => $iids[0], 'update_remarks' => 'ok',
        ]);
        $this->ok('R1.1 approval returns success', empty($res['error']), json_encode($res));
        $it = $this->item($iids[0]);
        $this->eq('R1.2 refund amount = item sub_total', 1000.0, $it['refund_amount']);
        $this->ok('R1.3 refunded_at stamped', !empty($it['refunded_at']));
        $this->eq('R1.4 refund_mode wallet', 'wallet', $it['refund_mode']);
        $this->eq('R1.5 customer wallet credited once', $bal_before + 1000, $this->bal($cust));
        $this->eq('R1.6 stock restored once', $stock_before + 1, $this->stock($vid));
        $this->ok('R1.7 stock_restored_at stamped', !empty($it['stock_restored_at']));
        $this->eq('R1.8 request marked approved', '1', $this->db->where('id', $rr)->get('return_requests')->row()->status);
        $this->eq('R1.9 order total reduced to remaining item', 1000.0, $this->order($oid)['total']);
        $txn = $this->db->where(['order_item_id' => $iids[0], 'user_id' => $cust])->get('transactions')->result_array();
        $this->eq('R1.10 exactly one refund transaction row', 1, count($txn));

        $this->section('R2  double approval / double refund guards');
        $res2 = $this->Return_request_model->update_return_request([
            'return_request_id' => $rr, 'status' => '1', 'order_item_id' => $iids[0],
        ]);
        $this->ok('R2.1 re-approving the same request is refused', !empty($res2['error']), json_encode($res2));
        $this->eq('R2.2 wallet unchanged after re-approval', $bal_before + 1000, $this->bal($cust));
        $this->eq('R2.3 stock unchanged after re-approval', $stock_before + 1, $this->stock($vid));

        $rf = process_refund($iids[0], 'returned', 'order_items');
        $this->ok('R2.4 process_refund short-circuits on an already refunded item', !empty($rf['already_refunded']), json_encode($rf));
        $this->eq('R2.5 wallet unchanged by direct re-refund', $bal_before + 1000, $this->bal($cust));
        $restored = restore_order_item_stock($iids[0], 'again');
        $this->ok('R2.6 restore_order_item_stock is idempotent', $restored === false);
        $this->eq('R2.7 stock unchanged by second restore', $stock_before + 1, $this->stock($vid));

        $this->section('R3  rejection does not refund or restock');
        $cust3 = $this->mk_user(0, 'zt_buyer3');
        list($pid3, $vid3) = $this->mk_product($seller, 800, 50, 1);
        list($oid3, $iids3) = $this->mk_order($cust3, [['variant_id' => $vid3, 'seller_id' => $seller, 'qty' => 2, 'price' => 800]]);
        $this->db->insert('return_requests', [
            'user_id' => $cust3, 'product_id' => $pid3, 'product_variant_id' => $vid3,
            'order_id' => $oid3, 'order_item_id' => $iids3[0], 'status' => 0,
        ]);
        $rr3 = $this->db->insert_id();
        $b3 = $this->bal($cust3);
        $s3 = $this->stock($vid3);
        $res3 = $this->Return_request_model->update_return_request([
            'return_request_id' => $rr3, 'status' => '2', 'order_item_id' => $iids3[0], 'update_remarks' => 'not eligible',
        ]);
        $this->ok('R3.1 rejection succeeds', empty($res3['error']), json_encode($res3));
        $this->eq('R3.2 request marked rejected', '2', $this->db->where('id', $rr3)->get('return_requests')->row()->status);
        $this->eq('R3.3 no wallet credit on rejection', $b3, $this->bal($cust3));
        $this->eq('R3.4 no stock restored on rejection', $s3, $this->stock($vid3));
        $it3 = $this->item($iids3[0]);
        $this->ok('R3.5 item not marked refunded', empty($it3['refunded_at']));
        $this->ok('R3.6 item active_status is return_request_decline', in_array($it3['active_status'], ['return_request_decline', 'received']), $it3['active_status']);

        $this->section('R4  refund maths with a promo code on a multi-item order');
        $cust4 = $this->mk_user(0, 'zt_buyer4');
        list($pid4, $vid4) = $this->mk_product($seller, 1000, 100, 1);
        $p4 = $this->mk_promo(['discount' => 10, 'discount_type' => 'percentage', 'max_discount_amount' => 1000, 'minimum_order_amount' => 500, 'repeat_usage' => 0]);
        $c4 = $this->code($p4);
        list($oid4, $iids4) = $this->mk_order($cust4, [
            ['variant_id' => $vid4, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000],
            ['variant_id' => $vid4, 'seller_id' => $seller, 'qty' => 1, 'price' => 1000],
        ], ['promo_code' => $c4, 'promo_discount' => 200, 'payment_method' => 'RazorPay']);
        $b4 = $this->bal($cust4);
        $rf4 = process_refund($iids4[0], 'returned', 'order_items');
        $this->ok('R4.1 refund processed', empty($rf4['error']), json_encode($rf4));
        // item 1000; discount goes 200 -> 100 on the remaining 1000 cart, so customer keeps 100 of discount
        $this->eq('R4.2 refund = item total minus the discount now forfeited (1000-200+100)', 900.0, $this->item($iids4[0])['refund_amount']);
        $this->eq('R4.3 wallet credited 900', $b4 + 900, $this->bal($cust4));
        $o4 = $this->order($oid4);
        $this->eq('R4.4 order total reduced', 1000.0, $o4['total']);
        $this->eq('R4.5 order promo_discount resized to 100', 100.0, $o4['promo_discount']);
        $this->eq('R4.6 order final_total = 900', 900.0, $o4['final_total']);

        $this->section('R5  returning the last item + returnable delivery charge');
        $b4b = $this->bal($cust4);
        $rf5 = process_refund($iids4[1], 'returned', 'order_items');
        $this->ok('R5.1 second refund processed', empty($rf5['error']), json_encode($rf5));
        $o5 = $this->order($oid4);
        $this->eq('R5.2 order zeroed out', 0.0, $o5['total']);
        $this->eq('R5.3 order final_total zeroed', 0.0, $o5['final_total']);
        $this->ok('R5.4 second refund credited something', $this->bal($cust4) > $b4b, 'bal=' . $this->bal($cust4));

        $cust5 = $this->mk_user(0, 'zt_buyer5');
        list($pid5, $vid5) = $this->mk_product($seller, 500, 20, 1);
        list($oid5, $iids5) = $this->mk_order($cust5, [['variant_id' => $vid5, 'seller_id' => $seller, 'qty' => 1, 'price' => 500]], [
            'delivery_charge' => 50, 'is_delivery_charge_returnable' => 1, 'payment_method' => 'RazorPay',
        ]);
        $b5 = $this->bal($cust5);
        process_refund($iids5[0], 'returned', 'order_items');
        $this->eq('R5.5 last item refund includes returnable delivery charge', 550.0, $this->item($iids5[0])['refund_amount']);
        $this->eq('R5.6 wallet credited 550', $b5 + 550, $this->bal($cust5));

        $cust6 = $this->mk_user(0, 'zt_buyer6');
        list($pid6, $vid6) = $this->mk_product($seller, 500, 20, 1);
        list($oid6, $iids6) = $this->mk_order($cust6, [['variant_id' => $vid6, 'seller_id' => $seller, 'qty' => 1, 'price' => 500]], [
            'delivery_charge' => 50, 'is_delivery_charge_returnable' => 0, 'payment_method' => 'RazorPay',
        ]);
        $b6 = $this->bal($cust6);
        process_refund($iids6[0], 'returned', 'order_items');
        $this->eq('R5.7 non-returnable delivery charge is NOT refunded', 500.0, $this->item($iids6[0])['refund_amount']);

        $this->section('R6  COD orders: nothing was paid, nothing is refunded');
        $cust7 = $this->mk_user(0, 'zt_buyer7');
        list($pid7, $vid7) = $this->mk_product($seller, 900, 30, 1);
        list($oid7, $iids7) = $this->mk_order($cust7, [['variant_id' => $vid7, 'seller_id' => $seller, 'qty' => 1, 'price' => 900]], ['payment_method' => 'COD']);
        $b7 = $this->bal($cust7);
        $rf7 = process_refund($iids7[0], 'returned', 'order_items');
        $this->eq('R6.1 COD with no wallet used refunds 0', 0.0, $this->item($iids7[0])['refund_amount']);
        $this->eq('R6.2 wallet untouched', $b7, $this->bal($cust7));
        $this->eq('R6.3 refund_mode none', 'none', $this->item($iids7[0])['refund_mode']);
        $this->ok('R6.4 item still closed out (refunded_at set)', !empty($this->item($iids7[0])['refunded_at']));

        $cust8 = $this->mk_user(0, 'zt_buyer8');
        list($pid8, $vid8) = $this->mk_product($seller, 900, 30, 1);
        list($oid8, $iids8) = $this->mk_order($cust8, [['variant_id' => $vid8, 'seller_id' => $seller, 'qty' => 1, 'price' => 900]], ['payment_method' => 'COD', 'wallet_balance' => 300]);
        $b8 = $this->bal($cust8);
        process_refund($iids8[0], 'returned', 'order_items');
        $this->eq('R6.5 COD part-paid from wallet refunds the wallet part', 300.0, $this->item($iids8[0])['refund_amount']);
        $this->eq('R6.6 wallet credited 300', $b8 + 300, $this->bal($cust8));

        $this->section('R7  commission reversal on an already-settled item');
        $seller9 = $this->mk_user(0, 'zt_seller9');
        $cust9 = $this->mk_user(0, 'zt_buyer9');
        list($pid9, $vid9) = $this->mk_product($seller9, 1000, 10, 1);
        list($oid9, $iids9) = $this->mk_order($cust9, [['variant_id' => $vid9, 'seller_id' => $seller9, 'qty' => 1, 'price' => 1000]]);
        $this->db->where('id', $iids9[0])->update('order_items', [
            'admin_commission_amount' => 100, 'seller_commission_amount' => 900,
            'commission_rate' => 10, 'is_credited' => 1, 'active_status' => 'delivered',
        ]);
        $this->db->insert('seller_settlements', [
            'seller_id' => $seller9, 'order_id' => $oid9, 'order_item_id' => $iids9[0],
            'order_amount' => 1000, 'commission_percent' => 10, 'commission_amount' => 100,
            'net_payable' => 900, 'settlement_status' => 'settled',
            'taxable_value' => 1000, 'product_tax_amount' => 0, 'commission_gst_amount' => 0,
            'tcs_amount' => 0, 'tds_amount' => 0, 'shipping_deduction' => 0, 'gateway_fee' => 0,
        ]);
        $this->db->set('balance', 900)->where('id', $seller9)->update('users');
        $sbal = $this->bal($seller9);
        process_refund($iids9[0], 'returned', 'order_items');
        $this->eq('R7.1 seller debited the net payable', $sbal - 900, $this->bal($seller9));
        $st = $this->db->where('order_item_id', $iids9[0])->get('seller_settlements')->row_array();
        $this->eq('R7.2 settlement marked reversed', 'reversed', $st['settlement_status']);
        $it9 = $this->item($iids9[0]);
        $this->eq('R7.3 admin commission zeroed', 0.0, $it9['admin_commission_amount']);
        $this->eq('R7.4 seller commission zeroed', 0.0, $it9['seller_commission_amount']);
        $this->eq('R7.5 is_credited stays 1 (no re-settlement)', '1', $it9['is_credited']);
        $rev = $this->Seller_model->reverse_settlement_for_order_item($iids9[0]);
        $this->ok('R7.6 reversal is idempotent', empty($rev['reversed']), json_encode($rev));
        $this->eq('R7.7 seller balance unchanged by second reversal', $sbal - 900, $this->bal($seller9));

        $this->section('R8  per-seller order_charges rewrite');
        $sellerA = $this->mk_user(0, 'zt_sA');
        $sellerB = $this->mk_user(0, 'zt_sB');
        $cust10 = $this->mk_user(0, 'zt_buyer10');
        list($pA, $vA) = $this->mk_product($sellerA, 1000, 10, 1);
        list($pB, $vB) = $this->mk_product($sellerB, 1000, 10, 1);
        list($oid10, $iids10) = $this->mk_order($cust10, [
            ['variant_id' => $vA, 'seller_id' => $sellerA, 'qty' => 1, 'price' => 1000],
            ['variant_id' => $vB, 'seller_id' => $sellerB, 'qty' => 1, 'price' => 1000],
        ], ['promo_code' => '', 'promo_discount' => 0]);
        process_refund($iids10[0], 'returned', 'order_items');
        $ocA = $this->db->where(['order_id' => $oid10, 'seller_id' => $sellerA])->get('order_charges')->row_array();
        $ocB = $this->db->where(['order_id' => $oid10, 'seller_id' => $sellerB])->get('order_charges')->row_array();
        $this->eq('R8.1 refunded seller parcel emptied', 0.0, $ocA['sub_total']);
        $this->eq('R8.2 refunded seller total 0', 0.0, $ocA['total']);
        $this->eq('R8.3 other seller parcel untouched', 1000.0, $ocB['sub_total']);

        $this->section('R9  whole-order refund');
        $cust11 = $this->mk_user(0, 'zt_buyer11');
        list($p11, $v11) = $this->mk_product($seller, 700, 10, 1);
        list($oid11, $iids11) = $this->mk_order($cust11, [
            ['variant_id' => $v11, 'seller_id' => $seller, 'qty' => 1, 'price' => 700],
            ['variant_id' => $v11, 'seller_id' => $seller, 'qty' => 1, 'price' => 700],
        ], ['payment_method' => 'RazorPay']);
        $b11 = $this->bal($cust11);
        $rf11 = process_refund($oid11, 'cancelled', 'orders');
        $this->ok('R9.1 order-level refund returns a status array', is_array($rf11) && isset($rf11['error']), json_encode($rf11));
        $this->eq('R9.2 whole order refunded to wallet', $b11 + 1400, $this->bal($cust11));
        $it11 = $this->item($iids11[0]);
        $this->ok('R9.3 every item closed out', !empty($it11['refunded_at']) && !empty($this->item($iids11[1])['refunded_at']));
        $again = process_refund($oid11, 'cancelled', 'orders');
        $this->ok('R9.4 second whole-order refund short-circuits', !empty($again['already_refunded']), json_encode($again));
        $this->eq('R9.5 wallet unchanged', $b11 + 1400, $this->bal($cust11));
        $perItem = process_refund($iids11[0], 'returned', 'order_items');
        $this->ok('R9.6 per-item refund after order refund short-circuits', !empty($perItem['already_refunded']), json_encode($perItem));
        $this->eq('R9.7 wallet still unchanged', $b11 + 1400, $this->bal($cust11));

        $this->section('R10  invalid inputs');
        $bad = process_refund($iids11[0], 'delivered', 'order_items');
        $this->ok('R10.1 non-refundable status rejected', !empty($bad['error']), json_encode($bad));
        $bad = process_refund(999999999, 'returned', 'order_items');
        $this->ok('R10.2 unknown order item rejected', !empty($bad['error']), json_encode($bad));
        $bad = process_refund(999999999, 'cancelled', 'orders');
        $this->ok('R10.3 unknown order rejected', !empty($bad['error']), json_encode($bad));
        $bad = process_refund($iids11[0], 'returned', 'nonsense');
        $this->ok('R10.4 unknown type rejected', !empty($bad['error']), json_encode($bad));

        $this->section('R11  duplicate return requests');
        $cust12 = $this->mk_user(0, 'zt_buyer12');
        list($p12, $v12) = $this->mk_product($seller, 600, 10, 1);
        list($oid12, $iids12) = $this->mk_order($cust12, [['variant_id' => $v12, 'seller_id' => $seller, 'qty' => 1, 'price' => 600]]);
        $row = ['user_id' => $cust12, 'product_id' => $p12, 'product_variant_id' => $v12, 'order_id' => $oid12, 'order_item_id' => $iids12[0]];
        set_user_return_request($row, 'order_items');
        set_user_return_request($row, 'order_items');
        $cnt = $this->db->where(['order_id' => $oid12, 'order_item_id' => $iids12[0]])->get('return_requests')->num_rows();
        $this->eq('R11.1 only one return request row per order item', 1, $cnt);

        $this->section('R12  stock restoration across stock types');
        // stock_type 0 = simple product, stock lives on products.stock
        $cust13 = $this->mk_user(0, 'zt_buyer13');
        list($p13, $v13) = $this->mk_product($seller, 400, 25, 1, '0');
        list($oid13, $iids13) = $this->mk_order($cust13, [['variant_id' => $v13, 'seller_id' => $seller, 'qty' => 3, 'price' => 400]]);
        $ps13 = $this->pstock($p13);
        restore_order_item_stock($iids13[0], 'Order item returned');
        $this->eq('R12.1 simple product stock restored on products.stock', $ps13 + 3, $this->pstock($p13));

        // legacy literal marker 'simple_product'
        $cust14 = $this->mk_user(0, 'zt_buyer14');
        list($p14, $v14) = $this->mk_product($seller, 400, 25, 1, 'simple_product');
        list($oid14, $iids14) = $this->mk_order($cust14, [['variant_id' => $v14, 'seller_id' => $seller, 'qty' => 12, 'price' => 400]]);
        $ps14 = $this->pstock($p14);
        restore_order_item_stock($iids14[0], 'Order item returned');
        $this->eq('R12.2 legacy simple_product marker restores full qty (12 not 1)', $ps14 + 12, $this->pstock($p14));

        // stock_type 2 = variant level
        $cust15 = $this->mk_user(0, 'zt_buyer15');
        list($p15, $v15) = $this->mk_product($seller, 400, 25, 1, '2');
        list($oid15, $iids15) = $this->mk_order($cust15, [['variant_id' => $v15, 'seller_id' => $seller, 'qty' => 4, 'price' => 400]]);
        $vs15 = $this->stock($v15);
        restore_order_item_stock($iids15[0], 'Order item returned');
        $this->eq('R12.3 variant level stock restored on product_variants.stock', $vs15 + 4, $this->stock($v15));

        // stock movement is labelled in stock_logs
        $log2 = $this->db->where('order_id', $oid15)->get('stock_logs')->row_array();
        $this->ok('R12.4 stock movement written to stock_logs with a reason', !empty($log2) && !empty($log2['reason']), json_encode($log2));

        $this->section('R13  return eligibility (is_returnable / already returned)');
        $cust16 = $this->mk_user(0, 'zt_buyer16');
        list($p16, $v16) = $this->mk_product($seller, 400, 25, 0, '1'); // NOT returnable
        list($oid16, $iids16) = $this->mk_order($cust16, [['variant_id' => $v16, 'seller_id' => $seller, 'qty' => 1, 'price' => 400]]);
        $this->db->where('id', $iids16[0])->update('order_items', ['active_status' => 'delivered']);
        $chk = validate_order_status($iids16[0], 'returned', 'order_items', $cust16, true);
        $this->ok('R13.1 non-returnable product cannot be returned', !empty($chk['error']), json_encode($chk['message'] ?? $chk));

        $cust17 = $this->mk_user(0, 'zt_buyer17');
        list($p17, $v17) = $this->mk_product($seller, 400, 25, 1, '1');
        list($oid17, $iids17) = $this->mk_order($cust17, [['variant_id' => $v17, 'seller_id' => $seller, 'qty' => 1, 'price' => 400]]);
        $chk = validate_order_status($iids17[0], 'returned', 'order_items', $cust17, true);
        $this->ok('R13.2 an undelivered item cannot be returned', !empty($chk['error']), json_encode($chk['message'] ?? $chk));

        $this->db->where('id', $iids17[0])->update('order_items', ['active_status' => 'returned']);
        $chk = validate_order_status($iids17[0], 'returned', 'order_items', $cust17, true);
        $this->ok('R13.3 an already returned item cannot be returned again', !empty($chk['error']), json_encode($chk['message'] ?? $chk));

        $this->section('R14  whole-order refund on an order that was never paid');
        $cust18 = $this->mk_user(0, 'zt_buyer18');
        list($p18, $v18) = $this->mk_product($seller, 500, 10, 1, '1');
        list($oid18, $iids18) = $this->mk_order($cust18, [
            ['variant_id' => $v18, 'seller_id' => $seller, 'qty' => 1, 'price' => 500],
            ['variant_id' => $v18, 'seller_id' => $seller, 'qty' => 1, 'price' => 500],
        ], ['payment_method' => 'RazorPay']);
        $awaiting = json_encode([['awaiting', date('Y-m-d H:i:s')], ['cancelled', date('Y-m-d H:i:s')]]);
        $this->db->where('order_id', $oid18)->update('order_items', ['status' => $awaiting, 'active_status' => 'cancelled']);
        $b18 = $this->bal($cust18);
        $r18 = process_refund($oid18, 'cancelled', 'orders');
        $this->ok('R14.1 awaiting -> cancelled order refunds nothing', !empty($r18['error']), json_encode($r18));
        $this->eq('R14.2 wallet untouched', $b18, $this->bal($cust18));

        // one paid line on the order means there IS something to refund
        $cust19 = $this->mk_user(0, 'zt_buyer19');
        list($p19, $v19) = $this->mk_product($seller, 500, 10, 1, '1');
        list($oid19, $iids19) = $this->mk_order($cust19, [
            ['variant_id' => $v19, 'seller_id' => $seller, 'qty' => 1, 'price' => 500],
            ['variant_id' => $v19, 'seller_id' => $seller, 'qty' => 1, 'price' => 500],
        ], ['payment_method' => 'RazorPay']);
        $this->db->where('id', $iids19[0])->update('order_items', ['status' => $awaiting]);
        $b19 = $this->bal($cust19);
        $r19 = process_refund($oid19, 'cancelled', 'orders');
        $this->ok('R14.3 mixed history still refunds', empty($r19['error']), json_encode($r19));
        $this->eq('R14.4 wallet credited the order total', $b19 + 1000, $this->bal($cust19));

        $this->section('R15  customer return window');
        $settings = get_settings('system_settings', true);
        $days = isset($settings['max_product_return_days']) ? (int) $settings['max_product_return_days'] : 0;
        echo "  (max_product_return_days = $days)
";
        $cust20 = $this->mk_user(0, 'zt_buyer20');
        list($p20, $v20) = $this->mk_product($seller, 500, 10, 1, '1');
        list($oid20, $iids20) = $this->mk_order($cust20, [['variant_id' => $v20, 'seller_id' => $seller, 'qty' => 1, 'price' => 500]]);
        $hist = json_encode([['received', date('Y-m-d H:i:s')], ['delivered', date('Y-m-d H:i:s')]]);
        $this->db->where('id', $iids20[0])->update('order_items', [
            'status' => $hist, 'active_status' => 'delivered', 'delivered_at' => date('Y-m-d H:i:s'),
        ]);
        $chk = validate_order_status($iids20[0], 'returned', 'order_items', $cust20, true);
        $this->ok('R15.1 delivered today is inside the return window', empty($chk['error']) || !empty($chk['return_request_flag']), json_encode($chk['message'] ?? $chk));

        $this->db->where('id', $iids20[0])->update('order_items', ['delivered_at' => date('Y-m-d H:i:s', strtotime('-' . ($days + 5) . ' days'))]);
        $chk = validate_order_status($iids20[0], 'returned', 'order_items', $cust20, true);
        $this->ok('R15.2 past the return window the customer is refused', !empty($chk['error']), json_encode($chk['message'] ?? $chk));
        $this->ok('R15.3 refusal names the window', isset($chk['message']) && stripos($chk['message'], 'return window') !== false, json_encode($chk['message'] ?? ''));

        $this->db->where('id', $iids20[0])->update('order_items', ['delivered_at' => null, 'status' => json_encode([['received', date('Y-m-d H:i:s')]])]);
        $chk = validate_order_status($iids20[0], 'returned', 'order_items', $cust20, true);
        $this->ok('R15.4 no delivery date on record is refused', !empty($chk['error']), json_encode($chk['message'] ?? $chk));

        $this->section('R16  whole-order stock restoration');
        $cust21 = $this->mk_user(0, 'zt_buyer21');
        list($p21, $v21) = $this->mk_product($seller, 300, 40, 1, '1');
        list($p22, $v22) = $this->mk_product($seller, 300, 40, 1, '1');
        list($oid21, $iids21) = $this->mk_order($cust21, [
            ['variant_id' => $v21, 'seller_id' => $seller, 'qty' => 2, 'price' => 300],
            ['variant_id' => $v22, 'seller_id' => $seller, 'qty' => 5, 'price' => 300],
        ]);
        $s21 = $this->stock($v21);
        $s22 = $this->stock($v22);
        $n = restore_order_stock($oid21, 'Order returned by admin');
        $this->eq('R16.1 every line restored, not just the first', 2, $n);
        $this->eq('R16.2 line 1 stock back', $s21 + 2, $this->stock($v21));
        $this->eq('R16.3 line 2 stock back', $s22 + 5, $this->stock($v22));
        $n = restore_order_stock($oid21, 'Order returned by admin');
        $this->eq('R16.4 a repeat call restores nothing', 0, $n);
        $this->eq('R16.5 stock unchanged by the repeat call', $s21 + 2, $this->stock($v21));

        // a line already restored by a per-item path is not restored again by the order sweep
        $cust22 = $this->mk_user(0, 'zt_buyer22');
        list($p23, $v23) = $this->mk_product($seller, 300, 40, 1, '1');
        list($oid22, $iids22) = $this->mk_order($cust22, [
            ['variant_id' => $v23, 'seller_id' => $seller, 'qty' => 3, 'price' => 300],
            ['variant_id' => $v23, 'seller_id' => $seller, 'qty' => 4, 'price' => 300],
        ]);
        $s23 = $this->stock($v23);
        restore_order_item_stock($iids22[0], 'Order item returned');
        $n = restore_order_stock($oid22, 'Order returned by admin');
        $this->eq('R16.6 order sweep only restores the outstanding line', 1, $n);
        $this->eq('R16.7 total restored is 3 + 4, never 3 + 3 + 4', $s23 + 7, $this->stock($v23));
    }
}

/** Stand-in for the Razorpay library, so the routing tests move no real money. */
class Ztest_fake_razorpay
{
    private $behaviour;
    public function __construct($behaviour = 'success')
    {
        $this->behaviour = $behaviour;
    }
    public function refund_payment($txn_id, $amount)
    {
        if ($this->behaviour === 'success') {
            return ['id' => 'rfnd_ZT' . strtoupper(substr(uniqid(), -8)), 'amount' => $amount * 100, 'payment_id' => $txn_id];
        }
        return ['http_code' => 400, 'body' => json_encode(['error' => ['description' => 'the payment has been fully refunded already']])];
    }
}
