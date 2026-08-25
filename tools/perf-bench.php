<?php
/**
 * Page benchmark for the performance work.
 *
 * Measures, for each URL: median TTFB over N runs, the number of SQL statements the
 * request executed (read from MySQL's global Questions counter), and the response
 * size. Run from the project root:
 *
 *     php tools/perf-bench.php [runs]
 *
 * Configure the target and DB below via environment variables if they differ:
 *   BENCH_BASE  (default http://localhost/cretzo)
 *   BENCH_DSN / BENCH_USER / BENCH_PASS
 */

$runs = isset($argv[1]) ? max(1, (int) $argv[1]) : 5;
$base = getenv('BENCH_BASE') ?: 'http://localhost/cretzo';

$urls = [
    'home'              => '/',
    'products'          => '/products',
    'product detail'    => '/products/details/colorful-cottage-knit-long-cardigan-1',
    'category'          => '/products/category/footwear-and-bags',
    'category 2'        => '/products/category/jewellery-and-accessories-1',
    'products sorted'   => '/products?sort=pv.price&order=asc',
    'products filtered' => '/products?min_price=100&max_price=5000',
    'sellers'           => '/sellers',
    'seller store'      => '/sellers/clayya',
    'cart'              => '/cart',
    'login'             => '/login',
    'contact'           => '/home/contact-us',
    'about'             => '/home/about-us',
    'faq'               => '/home/faq',
];

$dsn  = getenv('BENCH_DSN')  ?: 'mysql:host=127.0.0.1;port=3307';
$user = getenv('BENCH_USER') ?: 'root';
$pass = getenv('BENCH_PASS') ?: '';

$pdo = null;
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    fwrite(STDERR, "WARNING: no DB connection, query counts unavailable (" . $e->getMessage() . ")\n");
}

function questions($pdo)
{
    if (!$pdo) return null;
    $row = $pdo->query("SHOW GLOBAL STATUS LIKE 'Questions'")->fetch(PDO::FETCH_NUM);
    return (int) $row[1];
}

function fetch($url, &$code, &$bytes)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 120,
    ]);
    $start = microtime(true);
    $body  = curl_exec($ch);
    $ttfb  = curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME);
    if (!$ttfb) $ttfb = microtime(true) - $start;
    $code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $bytes = $body === false ? 0 : strlen($body);
    curl_close($ch);
    return $ttfb;
}

printf("%-19s %6s %8s %9s %9s %10s\n", 'PAGE', 'HTTP', 'QUERIES', 'MEDIAN', 'BEST', 'SIZE');
echo str_repeat('-', 68) . "\n";

$results = [];
foreach ($urls as $label => $path) {
    $url = $base . $path;

    // Warm once so autoloaders / OS caches do not skew the first sample.
    fetch($url, $code, $bytes);

    $q0 = questions($pdo);
    fetch($url, $code, $bytes);
    $q1 = questions($pdo);
    // The counter query itself costs 1; subtract it.
    $queries = ($q0 === null) ? null : max(0, $q1 - $q0 - 1);

    $times = [];
    for ($i = 0; $i < $runs; $i++) {
        $times[] = fetch($url, $code, $bytes);
    }
    sort($times);
    $median = $times[(int) floor(count($times) / 2)];
    $best   = $times[0];

    $results[$label] = compact('code', 'queries', 'median', 'best', 'bytes');
    printf(
        "%-19s %6d %8s %8.3fs %8.3fs %9s\n",
        $label,
        $code,
        $queries === null ? '-' : $queries,
        $median,
        $best,
        number_format($bytes)
    );
}

echo str_repeat('-', 68) . "\n";
$totalQ = 0; $totalT = 0; $errors = 0;
foreach ($results as $r) {
    $totalQ += (int) $r['queries'];
    $totalT += $r['median'];
    if ($r['code'] >= 400 || $r['code'] === 0) $errors++;
}
printf("%-19s %6s %8d %8.3fs\n", 'TOTAL', $errors ? "{$errors} ERR" : 'ok', $totalQ, $totalT);

file_put_contents(
    'tools/perf-bench-last.json',
    json_encode($results, JSON_PRETTY_PRINT) . "\n"
);
echo "\nWritten to tools/perf-bench-last.json\n";
exit($errors > 0 ? 1 : 0);
