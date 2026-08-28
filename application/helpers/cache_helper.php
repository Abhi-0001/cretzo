<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * =============================================================================
 *  CROSS-REQUEST CACHE
 * =============================================================================
 *
 * WHY THIS EXISTS
 * ---------------
 * Before this file the application had no cache layer at all - zero uses of
 * CodeIgniter's cache driver anywhere - so every request rebuilt everything from
 * MySQL. Profiling the storefront homepage with MySQL's general log showed 88
 * queries after the N+1 fixes, of which 24 were low-churn reference data that is
 * identical for every visitor and changes only when an administrator saves
 * something:
 *
 *     settings   9      themes    2
 *     categories 9      sliders   1
 *     languages  3      brands    1
 *
 * The header alone re-reads the category tree, the settings blobs and the
 * language list on EVERY page of the site, so this is not a homepage-only cost.
 *
 * WHY NOT CodeIgniter's OWN CACHE DRIVER
 * --------------------------------------
 * CI 3's `Cache_apc` driver is written against the `apc_*` API, which is dead on
 * PHP 7+; APCu exposes `apcu_*`, and the `apcu_bc` shim that provides the old
 * names is rarely installed. Loading CI's driver here would therefore silently
 * fall back to files even on a server that has APCu working perfectly. The file
 * driver also has no way to expire a group of related keys, which is exactly what
 * invalidation needs. This store is ~100 lines and does both.
 *
 * BACKENDS
 * --------
 * APCu when the extension is loaded and enabled (shared memory, no I/O), and a
 * plain file store under APPPATH.'cache/' otherwise. Hostinger shared hosting
 * generally has no Redis or Memcached, so the file store is the realistic
 * production backend; the APCu path costs nothing to keep and takes over
 * automatically if the extension ever appears.
 *
 * FAILURE BEHAVIOUR
 * -----------------
 * Every operation is best-effort. An unwritable cache directory, a serialisation
 * failure or a corrupt cache file must never surface to a shopper, so all of them
 * degrade to "no cache" - app_cache_remember() simply calls the producer and
 * returns its value, which is precisely the pre-cache behaviour. Nothing in here
 * can make a page fail that would otherwise have rendered.
 *
 * WHAT IS SAFE TO PUT IN HERE
 * ---------------------------
 * Reference data that is the same for every visitor. Do NOT cache anything
 * user-specific (cart contents, favourites, is_purchased) under a shared key,
 * and be deliberate about anything carrying a price or a stock level - see the
 * note on the homepage sections cache in Home.php.
 */

/**
 * Which backend is in use: 'apcu', 'file' or 'none'.
 *
 * Resolved once per request. 'none' disables the whole layer, which is what
 * happens when the cache directory cannot be created or written.
 */
function app_cache_backend()
{
    static $backend = null;
    if ($backend !== null) {
        return $backend;
    }

    if (function_exists('apcu_fetch') && function_exists('apcu_store') && ini_get('apc.enabled')) {
        return $backend = 'apcu';
    }

    $dir = app_cache_dir();
    if ($dir !== false) {
        return $backend = 'file';
    }

    return $backend = 'none';
}

/**
 * Absolute path to the cache directory, or FALSE when it is unusable.
 *
 * @return string|false
 */
function app_cache_dir()
{
    static $dir = null;
    if ($dir !== null) {
        return $dir;
    }

    $path = APPPATH . 'cache/app/';
    if (!is_dir($path)) {
        /* Suppressed on purpose: a race with a concurrent request creating the
         * same directory must not emit a warning into the response body. */
        @mkdir($path, 0755, true);
    }

    return $dir = (is_dir($path) && is_writable($path)) ? $path : false;
}

/**
 * Namespace applied to every key.
 *
 * Bumping this value invalidates the entire cache at once without touching a
 * single file - useful after a deploy that changes the SHAPE of anything cached
 * (adding a column to the category rows, say), where the stored payloads are
 * valid but no longer match what the code expects.
 */
function app_cache_namespace()
{
    return 'cz1';
}

/**
 * Maps a logical key to a filesystem-safe name.
 */
function app_cache_key($key)
{
    return app_cache_namespace() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $key);
}

/**
 * Reads a cached value.
 *
 * Returns the sentinel NULL for "not cached". A producer that legitimately
 * returns NULL is therefore re-run every time rather than cached - acceptable,
 * and it keeps the contract unambiguous without a wrapper object.
 *
 * @return mixed|null
 */
function app_cache_get($key)
{
    $backend = app_cache_backend();
    if ($backend === 'none') {
        return null;
    }

    $key = app_cache_key($key);

    if ($backend === 'apcu') {
        $ok = false;
        $value = apcu_fetch($key, $ok);
        return $ok ? $value : null;
    }

    $file = app_cache_dir() . $key . '.cache';
    if (!is_file($file)) {
        return null;
    }

    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') {
        return null;
    }

    $payload = @unserialize($raw);
    if (!is_array($payload) || !isset($payload['expires_at'])) {
        /* Corrupt or written by an older format - treat as a miss and drop it. */
        @unlink($file);
        return null;
    }

    if ($payload['expires_at'] < time()) {
        @unlink($file);
        return null;
    }

    return $payload['value'];
}

/**
 * Stores a value for $ttl seconds. Best-effort; returns whether it stuck.
 */
function app_cache_set($key, $value, $ttl = 300)
{
    $backend = app_cache_backend();
    if ($backend === 'none') {
        return false;
    }

    $ttl = max(1, (int) $ttl);
    $key = app_cache_key($key);

    if ($backend === 'apcu') {
        return (bool) apcu_store($key, $value, $ttl);
    }

    $raw = @serialize(array('expires_at' => time() + $ttl, 'value' => $value));
    if ($raw === false) {
        return false;
    }

    /*
     * Write to a unique temp file and rename over the target. rename() is atomic
     * within a filesystem, so a concurrent reader either sees the whole previous
     * file or the whole new one - never a half-written payload that would
     * unserialize to garbage.
     */
    $dir  = app_cache_dir();
    $tmp  = $dir . $key . '.' . getmypid() . '.tmp';
    if (@file_put_contents($tmp, $raw, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, $dir . $key . '.cache')) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/**
 * Drops one key.
 */
function app_cache_delete($key)
{
    $backend = app_cache_backend();
    if ($backend === 'none') {
        return;
    }

    $key = app_cache_key($key);

    if ($backend === 'apcu') {
        apcu_delete($key);
        return;
    }

    @unlink(app_cache_dir() . $key . '.cache');
}

/**
 * Drops every key beginning with $prefix.
 *
 * This is the invalidation primitive the callers actually want: "forget
 * everything about categories", without having to enumerate the variants of the
 * key (per language, per limit, per seller).
 */
function app_cache_delete_group($prefix)
{
    $backend = app_cache_backend();
    if ($backend === 'none') {
        return;
    }

    $prefix = app_cache_key($prefix);

    if ($backend === 'apcu') {
        /* APCUIterator is part of the APCu extension; guard anyway so an unusual
         * build cannot fatal here. */
        if (class_exists('APCUIterator')) {
            foreach (new APCUIterator('/^' . preg_quote($prefix, '/') . '/') as $item) {
                apcu_delete($item['key']);
            }
        }
        return;
    }

    $dir = app_cache_dir();
    foreach ((array) @glob($dir . $prefix . '*.cache') as $file) {
        @unlink($file);
    }
}

/**
 * The one function callers should normally use.
 *
 * Returns the cached value for $key, or runs $producer, caches what it returns
 * and hands it back.
 *
 *     $categories = app_cache_remember('nav_categories', 900, function () {
 *         return $this->category_model->get_categories(null, 12);
 *     });
 *
 * If the cache layer is unavailable this is exactly equivalent to calling
 * $producer() directly.
 *
 * @param string   $key
 * @param int      $ttl      Seconds.
 * @param callable $producer Must be side-effect free - it will not run on a hit.
 * @return mixed
 */
function app_cache_remember($key, $ttl, $producer)
{
    $cached = app_cache_get($key);
    if ($cached !== null) {
        return $cached;
    }

    $value = call_user_func($producer);

    /* NULL is the miss sentinel, so there is nothing sensible to store for it. */
    if ($value !== null) {
        app_cache_set($key, $value, $ttl);
    }

    return $value;
}
