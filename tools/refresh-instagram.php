<?php
/**
 * ============================================================================
 * Refresh the homepage Instagram strip.
 * ============================================================================
 *
 *   php tools/refresh-instagram.php
 *
 * The homepage strip used to be Curator.io's embed widget, loaded live in the
 * browser. That was dropped because the free plan injects a black "Powered by
 * Curator.io" tile INTO the grid and appends a credit line, and its script
 * verifies that attribution is still in the DOM - so the tile could not be
 * styled or hidden, only paid off. The strip is now our own markup over
 * self-hosted copies of Cretzo's own Instagram photos, which also means the
 * tile sizes are ours to set and the page no longer waits on a third-party
 * script to paint.
 *
 * The cost of that trade is this script: the strip is a snapshot, not a live
 * feed, so run it whenever the Instagram account has posts worth showing. It
 * only reads - it fetches the feed, writes the images and the manifest, and
 * touches nothing else.
 *
 * Output (all under assets/front_end/cretzo/img/instagram/):
 *   feed.json        - manifest the view reads: file, url, caption, ratio
 *   post-<n>.jpg     - one image per post, newest first
 *
 * Source: the same public Curator feed that fed the old widget. Curator is
 * still what aggregates the account, so if that feed is ever deleted this
 * script stops working - the already-downloaded images and manifest keep
 * rendering, and the replacement is Instagram's own Graph API (a long-lived
 * token on the business account) writing the same manifest shape.
 */

const CURATOR_FEED_ID = 'b22ac81e-28c4-4e42-8277-146ac29a87b1';

/* How many posts to keep. The strip shows 8; a couple spare means one bad post
 * can be dropped from the manifest by hand without leaving a hole. */
const KEEP_POSTS = 10;

$root    = dirname(__DIR__);
$out_dir = $root . '/assets/front_end/cretzo/img/instagram';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

if (!is_dir($out_dir) && !mkdir($out_dir, 0755, true)) {
    fwrite(STDERR, "Cannot create $out_dir\n");
    exit(1);
}

function fetch(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_USERAGENT      => 'cretzo-instagram-refresh/1.0',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false || $code !== 200) {
        fwrite(STDERR, "GET $url failed (HTTP $code) $err\n");
        exit(1);
    }
    return $body;
}

$api = 'https://api.curator.io/v1.1/feeds/' . CURATOR_FEED_ID . '/posts?limit=' . KEEP_POSTS;
$raw = json_decode(fetch($api), true);

if (!is_array($raw) || empty($raw['posts'])) {
    fwrite(STDERR, "Feed returned no posts.\n");
    exit(1);
}

$manifest = [];
$n        = 0;

foreach ($raw['posts'] as $post) {
    /* Text-only posts have nothing to show in a photo grid. */
    if (empty($post['has_image']) || empty($post['image'])) {
        continue;
    }

    $n++;
    /* image_large (850px) is enough for the largest tile at 2x; the xlarge
     * 1024 version is not worth the extra weight on a decorative strip. */
    $src  = !empty($post['image_large']) ? $post['image_large'] : $post['image'];
    $file = 'post-' . $n . '.jpg';

    file_put_contents($out_dir . '/' . $file, fetch($src));

    /* Captions run to hashtag walls; the view only needs a short alt text. */
    $caption = isset($post['text']) ? trim(preg_replace('/\s+/u', ' ', $post['text'])) : '';
    if (function_exists('mb_substr') && mb_strlen($caption, 'UTF-8') > 120) {
        $caption = rtrim(mb_substr($caption, 0, 117, 'UTF-8')) . '...';
    }

    $manifest[] = [
        'file'    => $file,
        'url'     => isset($post['url']) ? $post['url'] : '',
        'caption' => $caption,
    ];

    printf("  %-12s %s\n", $file, $src);

    if ($n >= KEEP_POSTS) {
        break;
    }
}

if ($manifest === []) {
    fwrite(STDERR, "No image posts found - manifest left untouched.\n");
    exit(1);
}

file_put_contents(
    $out_dir . '/feed.json',
    json_encode([
        'profile'    => isset($raw['sources'][0]['name']) ? ltrim($raw['sources'][0]['name'], '@') : 'cretzo_',
        'updated_at' => gmdate('c'),
        'posts'      => $manifest,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
);

echo count($manifest) . " posts written to assets/front_end/cretzo/img/instagram/\n";
