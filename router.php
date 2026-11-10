<?php
/**
 * Router dla wbudowanego serwera PHP:
 *   php -S 127.0.0.1:8080 router.php
 *
 * Na hostingu ALTe (Apache) używane jest .htaccess — ten plik nie jest potrzebny.
 */
declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$publicFile = __DIR__ . '/public' . $uri;

if ($uri !== '/' && is_file($publicFile)) {
    $ext = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));
    $types = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff2' => 'font/woff2',
        'txt' => 'text/plain; charset=utf-8',
        'xml' => 'application/xml; charset=utf-8',
    ];
    if (isset($types[$ext])) {
        header('Content-Type: ' . $types[$ext]);
        header('Cache-Control: public, max-age=86400');
        readfile($publicFile);
        return true;
    }
}

if ($uri === '/favicon.ico') {
    $ico = __DIR__ . '/public/img/ico.jpg';
    if (is_file($ico)) {
        header('Content-Type: image/jpeg');
        readfile($ico);
        return true;
    }
}

require __DIR__ . '/index.php';
return true;
