<?php

declare(strict_types=1);

// The built-in PHP server serves .css files without compression. Serve only
// this generated public stylesheet with HTTP caching and optional gzip.
$source = __DIR__.'/chapter-bundle.css';
if (!is_file($source) || !is_readable($source)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Stylesheet unavailable. Run npm run build:chapter-css.';
    return;
}

$modified = filemtime($source);
$etag = 'W/"'.dechex($modified).'-'.dechex(filesize($source)).'"';
header('Content-Type: text/css; charset=UTF-8');
header('Cache-Control: public, max-age=86400, must-revalidate');
header('ETag: '.$etag);
header('Last-Modified: '.gmdate('D, d M Y H:i:s', $modified).' GMT');
header('Vary: Accept-Encoding');

if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    return;
}

$acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
$acceptsGzip = preg_match('/(?:^|,)\s*gzip(?:\s*;\s*q=([0-9.]+))?/i', $acceptEncoding, $matches)
    && (!isset($matches[1]) || (float) $matches[1] > 0);

if ($acceptsGzip && function_exists('gzencode')) {
    $body = gzencode(file_get_contents($source), 6);
    header('Content-Encoding: gzip');
    header('Content-Length: '.strlen($body));
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') echo $body;
    return;
}

header('Content-Length: '.filesize($source));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') readfile($source);
