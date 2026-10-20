<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * =============================================================================
 *  WORK THAT RUNS AFTER THE BROWSER HAS ITS ANSWER
 * =============================================================================
 *
 * WHY THIS EXISTS
 * ---------------
 * Placing an order used to do all of its follow-up work before replying: an email to the
 * support inbox, one per seller, the notify_event() emails to customer and seller, the push
 * notification, and the Shiprocket booking. Every send_mail() opens a brand new SMTP session
 * to Gmail - measured locally at ~3.4s for the TLS handshake and EHLO alone, before it has
 * logged in or sent a byte - and the Shiprocket booking is several more HTTPS round trips.
 * The customer sat on "Please Wait..." for all of it, ~20s+ on a one-seller order, while the
 * order itself had been committed in the first few hundred milliseconds.
 *
 * None of that work can change what the customer is told: the order exists, the payment is
 * verified, stock is deducted. So it is queued here and run once the response is on the wire.
 *
 * HOW
 * ---
 * run_after_response() queues a callable and, on first use, opens an output buffer so the
 * whole response body is held in memory (otherwise a body over php.ini's 4KB output_buffering
 * would already be half-sent with no Content-Length, and the browser would have to wait for
 * the connection to close anyway). At shutdown the response is completed with whichever
 * mechanism the server offers:
 *
 *   - fastcgi_finish_request()   PHP-FPM
 *   - litespeed_finish_request() LiteSpeed / LSAPI - what Hostinger runs
 *   - Content-Length + Connection: close + flush   Apache mod_php (local XAMPP)
 *
 * and only then are the queued tasks run.
 *
 * The session is closed first. CI's file session driver holds an exclusive lock on the
 * session file for the life of the request, and the very next thing the browser does is
 * load payment/success with the same session - it would sit blocked behind our emails.
 *
 * FAILURE BEHAVIOUR
 * -----------------
 * Each task runs inside its own try/catch, so one failing email cannot stop the Shiprocket
 * booking that follows it. Failures are logged; nothing can reach the customer any more, and
 * nothing here can turn a placed order into a failed checkout. On the CLI (cron jobs,
 * migrations) there is no browser waiting, so tasks simply run immediately.
 */

function &after_response_queue()
{
    static $queue = [];
    return $queue;
}

/**
 * Output-buffer depth outside any view, captured when this (autoloaded) helper is included -
 * before a controller can render anything.
 *
 * CI's Loader wraps every view in its own ob_start() and decides how to close it by comparing
 * ob_get_level() with the depth it saw at start-up. A buffer opened by us INSIDE a view would
 * shift that count and make the Loader flush a view's markup straight out instead of handing it
 * to the Output class, scrambling the page. get_image_url() queues work from inside views, so
 * the buffer is only opened when nothing is nested; a view-rendered page is already held by
 * CI's own buffers until Output::_display() anyway.
 */
function after_response_base_ob_level()
{
    static $level = null;
    if ($level === null) {
        $level = ob_get_level();
    }
    return $level;
}
after_response_base_ob_level();

/**
 * Queue $task to run after the HTTP response has been delivered.
 *
 * @param callable $task  takes no arguments; capture what it needs with `use (...)`.
 * @param string   $label named in the log if the task throws.
 */
function run_after_response(callable $task, $label = 'task')
{
    if (is_cli()) {
        after_response_run_one($task, $label);
        return;
    }

    $queue = &after_response_queue();
    if (empty($queue)) {
        // Hold everything printed from here on, so the full body and its length are known
        // when the response is finished. 0 = no chunk size, never auto-flush. Not when called
        // from inside a view - see after_response_base_ob_level().
        if (ob_get_level() <= after_response_base_ob_level()) {
            ob_start(null, 0);
        }
        register_shutdown_function('after_response_flush');
    }
    $queue[] = [$task, $label];
}

function after_response_run_one(callable $task, $label)
{
    try {
        $task();
    } catch (Throwable $e) {
        log_message('error', 'after-response ' . $label . ' failed: ' . $e->getMessage()
            . ' @ ' . $e->getFile() . ':' . $e->getLine());
    }
}

/** Shutdown handler: finish the response, then drain the queue. */
function after_response_flush()
{
    $queue = &after_response_queue();
    if (empty($queue)) {
        return;
    }
    $tasks = $queue;
    $queue = [];

    ignore_user_abort(true);
    @set_time_limit(120);

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    after_response_finish_http();

    foreach ($tasks as $entry) {
        after_response_run_one($entry[0], $entry[1]);
    }
}

/** Deliver everything printed so far and tell the client the response is complete. */
function after_response_finish_http()
{
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
        return;
    }
    if (function_exists('litespeed_finish_request')) {
        litespeed_finish_request();
        return;
    }

    // mod_php: collect every open buffer (innermost holds the latest output) and send the
    // body with an exact length, so the browser treats it as complete without waiting for
    // the socket to close.
    $body = '';
    while (ob_get_level() > 0) {
        $body = ob_get_clean() . $body;
    }
    if (!headers_sent()) {
        // mod_deflate AND mod_brotli would otherwise hold the body until the request ends and
        // drop the Content-Length we are about to set. Both are switched on in .htaccess, and
        // browsers prefer br - turning off only gzip left every real browser (curl does not
        // ask for br) waiting on the queued work: found in a Chrome checkout run, where
        // "Placing your order..." sat for the whole 22s of deferred emails.
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
            @apache_setenv('no-brotli', '1');
        }
        header('Connection: close');
        header('Content-Length: ' . strlen($body));
    }
    echo $body;
    flush();
}
