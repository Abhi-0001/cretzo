<?php
/**
 * Backfill the thumb-sm / thumb-md copies that media uploads are supposed to have.
 *
 * WHY: resize_image() (helpers/function_helper.php) writes uploads/media/<year>/thumb-sm/<file>
 * (max 450px) and thumb-md/<file> (max 800px) at upload time, and get_image_url($p,'thumb','sm')
 * serves them. Images uploaded before that worked - or on a host without the GD extension - have no
 * thumbnail, so get_image_url() falls back to the ORIGINAL (150-350 KB) and every product table, the
 * POS grid and the listing pages download full-size photos for a 50px thumbnail.
 *
 * SAFE: dry run by default (prints what it would do); --apply writes. It never touches an original,
 * never overwrites an existing thumbnail, skips GIFs (animation) and images already no larger than
 * the target. Same names, sizes and folders as resize_image(), so nothing else needs to change.
 *
 *   php tools/backfill-thumbnails.php            # dry run
 *   php tools/backfill-thumbnails.php --apply    # create the missing thumbnails
 *
 * Needs the GD extension (php -m | grep gd). Run on the server that holds uploads/.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!extension_loaded('gd')) { fwrite(STDERR, "GD extension is not loaded - enable extension=gd in php.ini.\n"); exit(1); }

$apply = in_array('--apply', $argv, true);
$root  = realpath(__DIR__ . '/../uploads/media');
if (!$root) { fwrite(STDERR, "uploads/media not found.\n"); exit(1); }

$sizes = ['thumb-sm' => 450, 'thumb-md' => 800];
$stats = ['scanned' => 0, 'created' => 0, 'skipped_exists' => 0, 'skipped_small' => 0, 'skipped_other' => 0, 'failed' => 0, 'bytes_saved' => 0];

foreach (glob($root . '/*', GLOB_ONLYDIR) as $yearDir) {
    foreach (glob($yearDir . '/*.*') as $file) {            // top level only: thumb-*/cropped-* are subfolders
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) { $stats['skipped_other']++; continue; }
        $stats['scanned']++;
        $info = @getimagesize($file);
        if (!$info) { $stats['failed']++; continue; }
        [$w, $h] = $info;
        foreach ($sizes as $folder => $max) {
            $dest = $yearDir . '/' . $folder . '/' . basename($file);
            if (file_exists($dest)) { $stats['skipped_exists']++; continue; }
            if ($w <= $max && $h <= $max) { $stats['skipped_small']++; continue; }   // nothing to gain
            $scale = $max / max($w, $h);
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));
            if (!$apply) { echo "would create " . substr($dest, strlen($root) + 1) . " ({$w}x{$h} -> {$nw}x{$nh})\n"; $stats['created']++; continue; }

            $src = $ext === 'png' ? @imagecreatefrompng($file) : ($ext === 'webp' ? @imagecreatefromwebp($file) : @imagecreatefromjpeg($file));
            if (!$src) { $stats['failed']++; continue; }
            $dst = imagecreatetruecolor($nw, $nh);
            if ($ext === 'png' || $ext === 'webp') {           // keep transparency
                imagealphablending($dst, false); imagesavealpha($dst, true);
                imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            if (!is_dir(dirname($dest))) { mkdir(dirname($dest), 0775, true); }
            $ok = $ext === 'png' ? imagepng($dst, $dest, 7) : ($ext === 'webp' ? imagewebp($dst, $dest, 80) : imagejpeg($dst, $dest, 80));
            imagedestroy($src); imagedestroy($dst);
            if ($ok) { $stats['created']++; $stats['bytes_saved'] += max(0, filesize($file) - filesize($dest)); }
            else { $stats['failed']++; }
        }
    }
}
echo ($apply ? "APPLIED" : "DRY RUN (add --apply to write)") . "\n";
foreach ($stats as $k => $v) { echo str_pad($k, 16) . $v . "\n"; }
if ($apply) { echo "approx bytes saved per full list view: " . round($stats['bytes_saved'] / 1024) . " KB (sum over all thumbs)\n"; }
