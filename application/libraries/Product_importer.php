<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reads a public product page and returns the details a seller would otherwise
 * retype: name, description, images, brand, tags. Shared by the seller and admin
 * "Import from URL" panels so the safety rules live in exactly one place.
 *
 * The fetch is made by OUR server on behalf of a logged-in user, which makes it a
 * server-side-request-forgery risk: a user could point it at 127.0.0.1, the cloud
 * metadata address or an internal admin panel. Every request (and every redirect
 * hop, and every image) therefore goes through assert_public_url(), which resolves
 * the host and refuses private / loopback / link-local addresses.
 */
class Product_importer
{
    const MAX_HTML_BYTES  = 3145728;  // 3 MB of page
    const MAX_IMAGE_BYTES = 5242880;  // 5 MB per image
    const MAX_REDIRECTS   = 4;
    const MAX_IMAGES      = 12;
    const TIMEOUT         = 12;

    private $last_error = '';

    public function last_error()
    {
        return $this->last_error;
    }

    // ------------------------------------------------------------------
    // URL safety
    // ------------------------------------------------------------------

    /** Returns the normalised URL, or false (and sets last_error) if it is unsafe. */
    public function assert_public_url($url)
    {
        $url = trim((string) $url);
        if ($url === '' || strlen($url) > 2000) {
            return $this->fail('Please enter a valid product link.');
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        $parts = parse_url($url);
        if (!$parts || empty($parts['host']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return $this->fail('Please enter a valid product link.');
        }
        if (!empty($parts['user']) || !empty($parts['pass'])) {
            return $this->fail('Links with a username or password are not allowed.');
        }
        if (!empty($parts['port']) && !in_array((int) $parts['port'], [80, 443], true)) {
            return $this->fail('That link uses a port we cannot fetch from.');
        }
        $host = strtolower($parts['host']);
        if ($host === 'localhost' || substr($host, -6) === '.local' || substr($host, -9) === '.internal') {
            return $this->fail('That link points to a private address.');
        }
        foreach ($this->resolve_ips($host) as $ip) {
            if (!$this->is_public_ip($ip)) {
                return $this->fail('That link points to a private address.');
            }
        }
        if (!$this->resolve_ips($host)) {
            return $this->fail('We could not find that website. Check the link and try again.');
        }
        return $url;
    }

    private function resolve_ips($host)
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }
        $ips = [];
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $r) {
                if (!empty($r['ip'])) {
                    $ips[] = $r['ip'];
                } elseif (!empty($r['ipv6'])) {
                    $ips[] = $r['ipv6'];
                }
            }
        }
        if (!$ips) {
            $v4 = @gethostbynamel($host);
            if (is_array($v4)) {
                $ips = $v4;
            }
        }
        return $ips;
    }

    private function is_public_ip($ip)
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    // ------------------------------------------------------------------
    // HTTP
    // ------------------------------------------------------------------

    /**
     * GET with manual redirect handling so each hop is re-checked.
     * $max_bytes aborts the transfer as soon as the body grows past it.
     * Returns ['body' => , 'type' => , 'url' => final url] or false.
     */
    private function http_get($url, $max_bytes, $accept, $keep_error_body = false)
    {
        if (!function_exists('curl_init')) {
            return $this->fail('Import is not available on this server (cURL missing).');
        }
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $safe = $this->assert_public_url($url);
            if ($safe === false) {
                return false;
            }
            $body = '';
            $too_big = false;
            // Pin the connection to the address we just validated, so DNS cannot
            // answer differently between the check and the request (rebinding).
            $p = parse_url($safe);
            $port = !empty($p['port']) ? (int) $p['port'] : (strtolower($p['scheme']) === 'https' ? 443 : 80);
            $ips = $this->resolve_ips(strtolower($p['host']));
            $ch = curl_init($safe);
            if ($ips && !filter_var($p['host'], FILTER_VALIDATE_IP)) {
                curl_setopt($ch, CURLOPT_RESOLVE, [$p['host'] . ':' . $port . ':' . $ips[0]]);
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 6,
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_ENCODING       => '',
                CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                CURLOPT_HTTPHEADER     => [
                    'Accept: ' . $accept,
                    'Accept-Language: en-IN,en;q=0.9',
                ],
                CURLOPT_WRITEFUNCTION  => function ($ch, $chunk) use (&$body, &$too_big, $max_bytes) {
                    $body .= $chunk;
                    if (strlen($body) > $max_bytes) {
                        $too_big = true;
                        return 0; // abort
                    }
                    return strlen($chunk);
                },
            ]);
            // Headers first so the redirect target can be read.
            $location = '';
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $line) use (&$location) {
                if (stripos($line, 'location:') === 0) {
                    $location = trim(substr($line, 9));
                }
                return strlen($line);
            });
            curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $err  = curl_errno($ch);
            curl_close($ch);

            if ($too_big) {
                return $this->fail('That page or file is too large to import.');
            }
            if ($code >= 300 && $code < 400 && $location !== '') {
                $url = $this->absolutize($location, $safe);
                continue;
            }
            if ($err) {
                return $this->fail('We could not reach that website. It may be down or blocking automated access.');
            }
            if ($code === 403 || $code === 429 || $code === 503) {
                return $this->fail('That website blocked the import (it does not allow automated access). Open the "Link not fetching?" section below and use the one-click browser helper, or enter the details manually.');
            }
            if (($code < 200 || $code >= 300) && !($keep_error_body && $code >= 400 && $body !== '')) {
                return $this->fail('That website returned an error (' . $code . '). Check that the link opens in your browser.');
            }
            return ['body' => $body, 'type' => $type, 'url' => $safe, 'status' => $code];
        }
        return $this->fail('That link redirects too many times.');
    }

    private function absolutize($href, $base)
    {
        $href = trim(html_entity_decode((string) $href, ENT_QUOTES));
        if ($href === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }
        $b = parse_url($base);
        if (!$b || empty($b['host'])) {
            return $href;
        }
        $origin = $b['scheme'] . '://' . $b['host'] . (!empty($b['port']) ? ':' . $b['port'] : '');
        if (strpos($href, '//') === 0) {
            return $b['scheme'] . ':' . $href;
        }
        if ($href[0] === '/') {
            return $origin . $href;
        }
        $path = isset($b['path']) ? preg_replace('#/[^/]*$#', '/', $b['path']) : '/';
        // Collapse ./ and ../ so "../../media/x.jpg" becomes a clean absolute path.
        $segments = [];
        foreach (explode('/', $path . $href) as $seg) {
            if ($seg === '..') {
                array_pop($segments);
            } elseif ($seg !== '.') {
                $segments[] = $seg;
            }
        }
        return $origin . implode('/', $segments);
    }

    // ------------------------------------------------------------------
    // Page extraction
    // ------------------------------------------------------------------

    /**
     * Returns ['name','description','short_description','brand','tags','price',
     * 'mrp','images'=>[urls],'source'] or false.
     */
    public function fetch($url)
    {
        $res = $this->http_get($url, self::MAX_HTML_BYTES, 'text/html,application/xhtml+xml', true);
        if ($res === false) {
            return false;
        }
        if (stripos($res['type'], 'html') === false && stripos($res['type'], 'xml') === false && $res['type'] !== '') {
            return $this->fail('That link is not a web page.');
        }
        $html = $res['body'];

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        // The xml prefix makes libxml read the page as UTF-8 (mb HTML-ENTITIES is deprecated).
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        $xp = new DOMXPath($dom);

        $out = [
            'name' => '', 'description' => '', 'short_description' => '', 'brand' => '',
            'tags' => [], 'price' => '', 'mrp' => '', 'images' => [], 'source' => parse_url($res['url'], PHP_URL_HOST),
        ];

        // 1. JSON-LD Product - the most reliable source when a site publishes it.
        $this->from_json_ld($xp, $out, $res['url']);
        $has_product_data = ($out['name'] !== '');

        // A page that only ships an empty app shell (React/Vue/Next client rendering)
        // has nothing to read here, and its og: tags describe the site, not the product.
        // Importing those would silently fill the form with the homepage's details.
        $host = strtolower((string) parse_url($res['url'], PHP_URL_HOST));
        $is_marketplace = (strpos($host, 'amazon.') !== false || strpos($host, 'flipkart.') !== false);
        // A 404 shell carries the site-wide JSON-LD, not this product's, so a shell that
        // answers with an error status counts as having no product data of its own.
        if ((!$has_product_data || $res['status'] >= 400) && !$is_marketplace && $this->is_js_shell($dom, $xp)) {
            return $this->fail('This website builds its product pages with JavaScript, so we can only see an empty page and cannot read the product from it. Importing works with websites that show the product in the page itself (Shopify, WooCommerce, Wix, most marketplaces). Open the "Link not fetching?" section below and use the one-click browser helper, or enter the details manually.');
        }
        if ($res['status'] >= 400) {
            return $this->fail('That page was not found (error ' . $res['status'] . '). Check that the link opens the product in your browser.');
        }

        // 2. Open Graph / meta tags.
        $this->from_meta($xp, $out, $res['url']);
        // 3. Marketplace-specific gallery and bullets.
        $this->from_marketplace($xp, $dom, $out, $res['url'], $html);
        // 4. Generic fallback.
        $this->from_generic($xp, $out, $res['url']);

        $out['images'] = $this->clean_images($out['images']);
        // "Product name | Store name" -> keep the product part.
        $parts = preg_split('/\s+[|\x{2013}\x{2014}]\s+/u', (string) $out['name']);
        if ($parts && trim($parts[0]) !== '') {
            $out['name'] = $parts[0];
        }
        $out['name'] = $this->clean_text($out['name'], 200);
        $out['brand'] = $this->clean_text($out['brand'], 100);
        $out['description'] = $this->clean_html($out['description']);
        if ($out['short_description'] === '') {
            $out['short_description'] = $this->clean_text(strip_tags($out['description']), 280);
        } else {
            $out['short_description'] = $this->clean_text($out['short_description'], 280);
        }
        $out['tags'] = array_slice(array_values(array_unique(array_filter(array_map(function ($t) {
            return $this->clean_text($t, 40);
        }, $out['tags'])))), 0, 10);

        if ($out['name'] === '' && !$out['images'] && $out['description'] === '') {
            return $this->fail('We could not find product details on that page. It may load its content with JavaScript or block automated access. Please enter the details manually.');
        }
        return $out;
    }

    /**
     * True when the page is a client-rendered app: an empty mount point (#root, #app,
     * #__next) means the real content is built by JavaScript after load. Static text
     * elsewhere in the body (SEO copy, footer) does not change that.
     */
    private function is_js_shell($dom, $xp)
    {
        foreach ($xp->query('//*[@id="root" or @id="app" or @id="__next" or @id="___gatsby"]') as $mount) {
            $text = trim(preg_replace('/\s+/u', ' ', $mount->textContent));
            if (strlen($text) < 100) {
                return true;
            }
        }
        return false;
    }

    private function from_json_ld($xp, &$out, $base)
    {
        foreach ($xp->query('//script[@type="application/ld+json"]') as $node) {
            $json = json_decode(trim($node->textContent), true);
            if (!is_array($json)) {
                continue;
            }
            foreach ($this->find_products($json) as $p) {
                if ($out['name'] === '' && !empty($p['name'])) {
                    $out['name'] = (string) $p['name'];
                }
                if ($out['description'] === '' && !empty($p['description']) && is_string($p['description'])) {
                    $out['description'] = (string) $p['description'];
                }
                if ($out['brand'] === '' && !empty($p['brand'])) {
                    $out['brand'] = is_array($p['brand']) ? (string) ($p['brand']['name'] ?? '') : (string) $p['brand'];
                }
                if (!empty($p['image'])) {
                    $imgs = is_array($p['image']) ? $p['image'] : [$p['image']];
                    foreach ($imgs as $i) {
                        $u = is_array($i) ? ($i['url'] ?? ($i['contentUrl'] ?? '')) : $i;
                        if (is_string($u) && $u !== '') {
                            $out['images'][] = $this->absolutize($u, $base);
                        }
                    }
                }
                if (!empty($p['category']) && is_string($p['category'])) {
                    foreach (preg_split('/[>,\/]/', $p['category']) as $c) {
                        $out['tags'][] = $c;
                    }
                }
                $offers = $p['offers'] ?? null;
                if (is_array($offers)) {
                    if (isset($offers[0])) {
                        $offers = $offers[0];
                    }
                    if ($out['price'] === '') {
                        $price = $offers['price'] ?? ($offers['lowPrice'] ?? '');
                        if ($price !== '' && is_numeric(str_replace(',', '', (string) $price))) {
                            $out['price'] = (string) str_replace(',', '', (string) $price);
                        }
                    }
                }
            }
        }
    }

    private function find_products($node, $depth = 0)
    {
        $found = [];
        if (!is_array($node) || $depth > 6) {
            return $found;
        }
        $type = $node['@type'] ?? null;
        $types = is_array($type) ? $type : [$type];
        if (in_array('Product', $types, true)) {
            $found[] = $node;
        }
        foreach ($node as $child) {
            if (is_array($child)) {
                $found = array_merge($found, $this->find_products($child, $depth + 1));
            }
        }
        return $found;
    }

    private function meta($xp, $key)
    {
        foreach (['property', 'name'] as $attr) {
            $n = $xp->query('//meta[@' . $attr . '="' . $key . '"]/@content');
            if ($n->length) {
                return trim($n->item(0)->nodeValue);
            }
        }
        return '';
    }

    private function from_meta($xp, &$out, $base)
    {
        if ($out['name'] === '') {
            $out['name'] = $this->meta($xp, 'og:title') ?: $this->meta($xp, 'twitter:title');
        }
        if ($out['description'] === '') {
            $out['description'] = $this->meta($xp, 'og:description') ?: $this->meta($xp, 'description');
        }
        $img = $this->meta($xp, 'og:image') ?: $this->meta($xp, 'twitter:image');
        if ($img !== '') {
            $out['images'][] = $this->absolutize($img, $base);
        }
        if ($out['brand'] === '') {
            $out['brand'] = $this->meta($xp, 'product:brand') ?: $this->meta($xp, 'og:brand');
        }
        if ($out['price'] === '') {
            $p = $this->meta($xp, 'product:price:amount') ?: $this->meta($xp, 'og:price:amount');
            if ($p !== '') {
                $out['price'] = $p;
            }
        }
        $kw = $this->meta($xp, 'keywords');
        if ($kw !== '' && !$out['tags']) {
            $out['tags'] = array_slice(array_map('trim', explode(',', $kw)), 0, 8);
        }
        if ($out['name'] === '') {
            $t = $xp->query('//title');
            if ($t->length) {
                $out['name'] = trim($t->item(0)->textContent);
            }
        }
    }

    private function from_marketplace($xp, $dom, &$out, $base, $html)
    {
        $host = strtolower((string) parse_url($base, PHP_URL_HOST));

        if (strpos($host, 'amazon.') !== false || strpos($host, 'amzn.') !== false) {
            $t = $xp->query('//*[@id="productTitle"]');
            if ($t->length) {
                $out['name'] = trim($t->item(0)->textContent);
            }
            $b = $xp->query('//*[@id="feature-bullets"]//li//span[contains(@class,"a-list-item")]');
            $items = [];
            foreach ($b as $li) {
                $txt = trim($li->textContent);
                if ($txt !== '') {
                    $items[] = '<li>' . htmlspecialchars($txt) . '</li>';
                }
            }
            if ($items) {
                $out['description'] = '<ul>' . implode('', $items) . '</ul>';
            }
            $brand = $xp->query('//*[@id="bylineInfo"]');
            if ($brand->length && $out['brand'] === '') {
                $out['brand'] = trim(preg_replace('/^(Visit the |Brand:\s*)|(\s*Store)$/i', '', $brand->item(0)->textContent));
            }
            // The gallery is a JS blob: "hiRes":"https://m.media-amazon.com/images/I/....jpg"
            if (preg_match_all('/"hiRes"\s*:\s*"(https:[^"]+)"/', $html, $m)) {
                foreach ($m[1] as $u) {
                    $out['images'][] = stripcslashes($u);
                }
            }
        } elseif (strpos($host, 'flipkart.') !== false) {
            $t = $xp->query('//span[contains(@class,"VU-ZEz")] | //h1//span');
            if ($t->length && $out['name'] === '') {
                $out['name'] = trim($t->item(0)->textContent);
            }
            if (preg_match_all('#https://rukminim[0-9]*\.flixcart\.com/image/[^"\'\s\\\\]+#', $html, $m)) {
                foreach ($m[0] as $u) {
                    // The URLs carry {@width}x{@height} placeholders; ask for a large rendition.
                    $u = preg_replace('#/image/\d+/\d+/#', '/image/832/832/', $u);
                    $out['images'][] = str_replace(['{@width}', '{@height}', '{@quality}'], ['832', '832', '80'], $u);
                }
            }
        }
    }

    private function from_generic($xp, &$out, $base)
    {
        if ($out['name'] === '') {
            $h = $xp->query('//h1');
            if ($h->length) {
                $out['name'] = trim($h->item(0)->textContent);
            }
        }
        if (count($out['images']) < 3) {
            // Content images, skipping tiny icons / sprites / tracking pixels by name.
            foreach ($xp->query('//img') as $img) {
                $src = $img->getAttribute('data-zoom-image') ?: $img->getAttribute('data-src')
                    ?: $img->getAttribute('data-large') ?: $img->getAttribute('src');
                if ($src === '' || stripos($src, 'data:') === 0) {
                    continue;
                }
                if (preg_match('/(logo|icon|sprite|avatar|banner|badge|payment|flag|pixel|spinner|loading)/i', $src)) {
                    continue;
                }
                $w = (int) $img->getAttribute('width');
                $h = (int) $img->getAttribute('height');
                if (($w && $w < 150) || ($h && $h < 150)) {
                    continue;
                }
                $out['images'][] = $this->absolutize($src, $base);
                if (count($out['images']) >= self::MAX_IMAGES * 2) {
                    break;
                }
            }
        }
    }

    // ------------------------------------------------------------------
    // Cleaning
    // ------------------------------------------------------------------

    private function clean_images($images)
    {
        $seen = $clean = [];
        foreach ($images as $u) {
            $u = trim((string) $u);
            if (!preg_match('#^https?://#i', $u) || strlen($u) > 1500) {
                continue;
            }
            $key = preg_replace('#\?.*$#', '', $u);
            // Same image at different sizes (Amazon ._SX300_ / Shopify _200x) counts once.
            $key = preg_replace('#\._[A-Z0-9,_]+_\.#', '.', $key);
            $key = preg_replace('#_\d+x\d*(\.\w+)$#', '$1', $key);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $clean[] = $u;
            if (count($clean) >= self::MAX_IMAGES) {
                break;
            }
        }
        return $clean;
    }

    private function clean_text($s, $max)
    {
        $s = html_entity_decode(strip_tags((string) $s), ENT_QUOTES, 'UTF-8');
        $s = trim(preg_replace('/\s+/u', ' ', $s));
        if (function_exists('mb_substr')) {
            return mb_substr($s, 0, $max);
        }
        return substr($s, 0, $max);
    }

    /** Keeps a small formatting subset; drops scripts, styles, handlers and links' hrefs. */
    private function clean_html($html)
    {
        $html = (string) $html;
        if ($html === '') {
            return '';
        }
        if (strip_tags($html) === $html) {
            // Plain text: keep paragraph breaks.
            $paras = array_filter(array_map('trim', preg_split('/\R{2,}|\R/', $html)));
            return $paras ? '<p>' . implode('</p><p>', array_map('htmlspecialchars', $paras)) . '</p>' : '';
        }
        $html = preg_replace('#<(script|style|iframe|object|embed|form|noscript)\b.*?</\1>#is', '', $html);
        $html = strip_tags($html, '<p><br><ul><ol><li><b><strong><i><em><table><thead><tbody><tr><td><th><h3><h4>');
        // strip_tags keeps attributes on allowed tags; remove them all (onclick, style, ...).
        $html = preg_replace('#<(\w+)\s[^>]*>#', '<$1>', $html);
        return trim($html);
    }

    // ------------------------------------------------------------------
    // Image download (called when the user clicks Apply)
    // ------------------------------------------------------------------

    /**
     * Downloads one remote image into the media folder, registers it in the media
     * library exactly like a manual upload, and returns
     * ['name','sub_directory','url'] or false.
     */
    public function download_image($url, $seller_id)
    {
        $res = $this->http_get($url, self::MAX_IMAGE_BYTES, 'image/*');
        if ($res === false) {
            return false;
        }
        $info = @getimagesizefromstring($res['body']);
        $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
        if (!$info || !isset($allowed[$info[2]])) {
            return $this->fail('Not a supported image.');
        }
        if ($info[0] < 100 || $info[1] < 100) {
            return $this->fail('Image too small.');
        }
        $ext = $allowed[$info[2]];

        $ci = &get_instance();
        $ci->load->model('media_model');
        $sub = MEDIA_PATH . date('Y') . '/';
        $dir = FCPATH . $sub;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $file_name = 'imp-' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (file_put_contents($dir . $file_name, $res['body']) === false) {
            return $this->fail('Could not save image.');
        }

        $data = [
            'file_name'      => $file_name,
            'file_ext'       => '.' . $ext,
            'raw_name'       => pathinfo($file_name, PATHINFO_FILENAME),
            'file_size'      => round(strlen($res['body']) / 1024, 2),
            'sub_directory'  => $sub,
            'is_image'       => true,
            'image_width'    => $info[0],
            'image_height'   => $info[1],
            'image_type'     => $ext,
        ];
        $media_id = $ci->media_model->set_media($data, (int) $seller_id);
        if ($ext !== 'gif') {
            // CI's image library can print notices on newer PHP; they must not leak
            // into the JSON this request answers with.
            ob_start();
            resize_image($data, $dir, $media_id);
            ob_end_clean();
        }
        return [
            'name'          => $file_name,
            'sub_directory' => $sub,
            'url'           => base_url() . $sub . $file_name,
        ];
    }

    /**
     * Hourly cap per user, kept in the same table as the OTP limiter (scope
     * 'product_import'). Returns true if the request may proceed.
     */
    public function rate_limit_ok($user_id, $limit = 30, $window = 3600)
    {
        $ci = &get_instance();
        $now = time();
        $stamp = date('Y-m-d H:i:s', $now);
        $cut = date('Y-m-d H:i:s', $now - $window);
        $ident = 'u' . (int) $user_id;
        $row = $ci->db->select('attempts, window_start')->where(['scope' => 'product_import', 'identifier' => $ident])
            ->get('otp_rate_limits')->row_array();
        if ($row && strtotime($row['window_start']) > $now - $window && (int) $row['attempts'] >= $limit) {
            return $this->fail('You have imported a lot of links recently. Please try again in a little while.');
        }
        $ci->db->query(
            "INSERT INTO `otp_rate_limits` (`scope`, `identifier`, `attempts`, `window_start`, `updated_at`)
             VALUES ('product_import', ?, 1, ?, ?)
             ON DUPLICATE KEY UPDATE
                `attempts`     = IF(`window_start` <= ?, 1, `attempts` + 1),
                `window_start` = IF(`window_start` <= ?, VALUES(`window_start`), `window_start`),
                `updated_at`   = VALUES(`updated_at`)",
            [$ident, $stamp, $stamp, $cut, $cut]
        );
        return true;
    }

    private function fail($msg)
    {
        $this->last_error = $msg;
        return false;
    }
}
