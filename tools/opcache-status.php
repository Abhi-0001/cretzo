<?php
/**
 * Reports whether OPcache is active and how well it is performing.
 *
 * Run through the WEB server, not the CLI - opcache.enable_cli is deliberately off,
 * so a CLI run will always report "not enabled" even when the site is using it.
 * Copy this file somewhere web-accessible temporarily, or hit it via the CLI server:
 *
 *     php -S 127.0.0.1:8123 tools/opcache-status.php
 *     curl http://127.0.0.1:8123/
 *
 * Healthy numbers after the site has been browsed for a minute or two:
 *   enabled        true
 *   hit_rate       > 95%
 *   cache_full     false
 *   restarts (oom) 0        <- anything above 0 means memory_consumption is too low
 */
header('Content-Type: text/plain');

if (!function_exists('opcache_get_status')) {
    echo "OPcache extension is NOT loaded.\n";
    echo "Add `zend_extension=opcache` and the settings in docs/performance/opcache.ini\n";
    exit(1);
}

$status = @opcache_get_status(false);
if (!$status || empty($status['opcache_enabled'])) {
    echo "OPcache is loaded but NOT ENABLED for this SAPI (" . PHP_SAPI . ").\n";
    echo "Set opcache.enable=1 (and remember opcache.enable_cli is off by design).\n";
    exit(1);
}

$s = $status['opcache_statistics'];
$m = $status['memory_usage'];

printf("SAPI              : %s\n", PHP_SAPI);
printf("enabled           : true\n");
printf("cached scripts    : %d / %d slots\n", $s['num_cached_scripts'], $s['max_cached_keys']);
printf("hits              : %d\n", $s['hits']);
printf("misses            : %d\n", $s['misses']);
printf("hit rate          : %.2f%%\n", $s['opcache_hit_rate']);
printf("memory used       : %.1f MB\n", $m['used_memory'] / 1048576);
printf("memory free       : %.1f MB\n", $m['free_memory'] / 1048576);
printf("wasted            : %.1f MB (%.1f%%)\n", $m['wasted_memory'] / 1048576, $m['current_wasted_percentage']);
printf("oom restarts      : %d\n", $s['oom_restarts']);
printf("hash restarts     : %d\n", $s['hash_restarts']);

$warn = [];
if ($s['opcache_hit_rate'] < 90 && $s['hits'] > 500)  $warn[] = "hit rate below 90% - is validate_timestamps thrashing, or is the file count above max_accelerated_files?";
if ($m['free_memory'] < 1048576)                      $warn[] = "cache is effectively full - raise opcache.memory_consumption";
if ($s['oom_restarts'] > 0)                           $warn[] = "out-of-memory restarts have occurred - raise opcache.memory_consumption";
if ($s['num_cached_scripts'] >= $s['max_cached_keys'] * 0.9) $warn[] = "approaching max_accelerated_files - raise it";

echo "\n" . (empty($warn) ? "OK - no issues detected.\n" : "WARNINGS:\n  - " . implode("\n  - ", $warn) . "\n");
