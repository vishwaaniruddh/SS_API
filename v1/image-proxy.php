<?php
/**
 * Image Proxy — serves resized WebP versions of product images.
 * 
 * Usage: /API/v1/image-proxy.php?url=<encoded_url>&w=<width>&q=<quality>
 */

$url = $_GET['url'] ?? '';
$width = min((int)($_GET['w'] ?? 400), 1200);
$quality = min(max((int)($_GET['q'] ?? 70), 10), 100);

if (empty($url)) {
    // Return transparent 1x1 pixel instead of error
    header('Content-Type: image/webp');
    header('Cache-Control: public, max-age=86400');
    echo base64_decode('UklGRlYAAABXRUJQVlA4IEoAAADQAQCdASoBAAEAAkA4JZQCdAEO/hepgAAA/v3Nf/bpf7f/t3uHuHe2f8P/4H/J/6X/W/7L/sP/V/7v/2f+5/8H/y//n/+r/9v////7v/+4AAAA');
    exit;
}

// Create cache directory inside API folder
$cacheDir = __DIR__ . '/../cache/images';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

// Generate cache key
$cacheKey = md5($url . $width . $quality) . '.webp';
$cachePath = $cacheDir . '/' . $cacheKey;

// Serve from cache if exists and fresh (7 days)
if (file_exists($cachePath) && (time() - filemtime($cachePath)) < 604800) {
    header('Content-Type: image/webp');
    header('Cache-Control: public, max-age=2592000');
    header('X-Cache: HIT');
    readfile($cachePath);
    exit;
}

// Download original image
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || empty($imageData)) {
    // Return transparent 1x1 WebP — no error, no console noise
    header('Content-Type: image/webp');
    header('Cache-Control: public, max-age=86400');
    echo base64_decode('UklGRlYAAABXRUJQVlA4IEoAAADQAQCdASoBAAEAAkA4JZQCdAEO/hepgAAA/v3Nf/bpf7f/t3uHuHe2f8P/4H/J/6X/W/7L/sP/V/7v/2f+5/8H/y//n/+r/9v////7v/+4AAAA');
    exit;
}

// Convert with GD (if available)
if (!function_exists('imagecreatefromstring')) {
    // GD not available — serve original image as-is
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=86400');
    echo $imageData;
    exit;
}

$src = @imagecreatefromstring($imageData);
if (!$src) {
    // Can't process — serve original as-is
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=86400');
    echo $imageData;
    exit;
}

$origW = imagesx($src);
$origH = imagesy($src);

// Only resize if larger than target
if ($origW > $width) {
    $ratio = $width / $origW;
    $newW = $width;
    $newH = (int)($origH * $ratio);
    
    $dst = imagecreatetruecolor($newW, $newH);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    imagedestroy($src);
    $src = $dst;
}

// Save as WebP
imagewebp($src, $cachePath, $quality);
imagedestroy($src);

// Serve
header('Content-Type: image/webp');
header('Cache-Control: public, max-age=2592000');
header('X-Cache: MISS');
readfile($cachePath);
