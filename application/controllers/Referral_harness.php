<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * =============================================================================
 *  REFERRAL TEST HARNESS - CLI ONLY
 * =============================================================================
 *
 * The thin CLI surface `tools/referral-e2e.sh` drives. It exists because the
 * referral programme's entry points are called from inside request handlers that
 * need an admin session, an order status change or a cron token - none of which
 * a shell script can produce cheaply - while the behaviour worth testing is the
 * engine's, not the plumbing's.
 *
 * Every method here is a one-line pass-through to code that ships. It contains
 * no logic of its own on purpose: a harness that computes anything is a harness
 * that can pass while the product is broken.
 *
 *     php index.php referral_harness delivered <user_id> <order_id>
 *     php index.php referral_harness release
 *     ...
 *
 * NOT REACHABLE OVER HTTP. is_cli() is checked before anything else runs, and a
 * web request gets a 404 - these methods move real money, and several of them
 * take a user id as an argument with no authentication behind it. The guard is
 * the only thing standing between this file and a wallet-draining endpoint, so
 * do not add a browser-testable branch to it.
 */
class Referral_harness extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!is_cli()) {
            show_404();
        }

        $this->load->database();
        $this->load->library('referral_engine');
    }

    /** An order reaching "delivered", through the same function the 9 sites call. */
    public function delivered($user_id, $order_id)
    {
        echo json_encode(process_referral_bonus((int) $user_id, (int) $order_id, 'delivered')) . PHP_EOL;
    }

    /** The same, for a return. */
    public function returned($user_id, $order_id)
    {
        echo json_encode(process_referral_bonus((int) $user_id, (int) $order_id, 'returned')) . PHP_EOL;
    }

    /** A referred seller's shop going live. */
    public function approved($user_id)
    {
        echo json_encode($this->referral_engine->seller_approved((int) $user_id)) . PHP_EOL;
    }

    /** The nightly release run, without the cron token. */
    public function release()
    {
        echo json_encode($this->referral_engine->release_due_rewards()) . PHP_EOL;
    }

    /** A wallet debit, to check that restricted credit is consumed first. */
    public function spend($user_id, $amount)
    {
        echo json_encode(update_wallet_balance('debit', (int) $user_id, (float) $amount, 'Test spend')) . PHP_EOL;
    }

    public function withdrawable($user_id)
    {
        echo $this->referral_engine->withdrawable_balance((int) $user_id) . PHP_EOL;
    }

    /** Whether a given account may redeem a given promo code. */
    public function promo($code, $user_id, $total)
    {
        $res = validate_promo_code($code, (int) $user_id, (float) $total);
        echo json_encode(['error' => $res['error'], 'message' => $res['message']]) . PHP_EOL;
    }

    /** The seller withdrawal path, including its spend-only guard. */
    public function withdraw($user_id, $amount)
    {
        $this->load->model('payment_request_model');
        $res = $this->payment_request_model->create_withdrawal_request((int) $user_id, 'seller', (float) $amount, 'test-address');
        echo json_encode(['error' => $res['error'], 'message' => $res['message']]) . PHP_EOL;
    }

    /**
     * Bind a referral directly, without going through a signup.
     *
     * The refusals worth testing - self-referral, a shared email or mobile,
     * binding twice - cannot be reached through the signup form, because a brand
     * new account has no way to submit its own code. This is the only way to
     * exercise them.
     */
    public function bind($referee_id, $code, $source = 'link')
    {
        echo json_encode(referral_bind((int) $referee_id, $code, [], $source)) . PHP_EOL;
    }

    /** Ambassador tier evaluation for one referrer. */
    public function tiers($user_id)
    {
        echo json_encode([
            'qualified' => $this->referral_engine->qualified_referral_count((int) $user_id),
            'awarded'   => $this->referral_engine->award_ambassador_tiers((int) $user_id),
        ]) . PHP_EOL;
    }
}
