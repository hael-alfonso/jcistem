<?php

// Used by `php artisan serve` when this project-root file exists at startup;
// also usable with `php -S 127.0.0.1:8000 -t public server.php`.
// Keep private application responses on Laravel's normal path.
$publicPath = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: __DIR__.'/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
$assetPath = realpath($publicPath.$uri);
$assetRoot = realpath($publicPath.'/assets');

if (in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)
    && $assetRoot && $assetPath && str_starts_with($assetPath, $assetRoot.DIRECTORY_SEPARATOR)
    && is_file($assetPath) && is_readable($assetPath)) {
    $extension = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));
    $types = [
        'css' => 'text/css; charset=UTF-8', 'js' => 'text/javascript; charset=UTF-8',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
        'woff' => 'font/woff', 'woff2' => 'font/woff2',
    ];

    if (isset($types[$extension])) {
        $modified = filemtime($assetPath);
        $etag = 'W/"'.dechex($modified).'-'.dechex(filesize($assetPath)).'"';
        header('Content-Type: '.$types[$extension]);
        header('Cache-Control: public, max-age='.(in_array($extension, ['css', 'js'], true) ? 86400 : 2592000));
        header('ETag: '.$etag);
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $modified).' GMT');
        if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            return true;
        }

        if (in_array($extension, ['css', 'js'], true)) {
            header('Vary: Accept-Encoding');
            if (function_exists('gzencode') && str_contains($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip')) {
                $body = gzencode(file_get_contents($assetPath), 6);
                header('Content-Encoding: gzip');
                header('Content-Length: '.strlen($body));
                if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') echo $body;
                return true;
            }
        }

        header('Content-Length: '.filesize($assetPath));
        if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') readfile($assetPath);
        return true;
    }
}

// Preserve Laravel's default router behavior for every other URL.
if ($uri !== '/' && file_exists($publicPath.$uri)) return false;

$formattedDateTime = date('D M j H:i:s Y');
$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];
file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");
require_once $publicPath.'/index.php';
