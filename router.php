<?php
/**
 * Router dla wbudowanego serwera PHP:
 *   php -S 127.0.0.1:8080 router.php
 *
 * Na hostingu ALTe (Apache) używane jest .htaccess — ten plik nie jest potrzebny.
 */
declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

// Blokada bezpośredniego HTTP do ścieżek wrażliwych (php -S nie czyta .htaccess).
if (
    preg_match('#^/(data|lib)(/|$)#i', $uri)
    || preg_match('#^/config(\.example)?\.php$#i', $uri)
    || preg_match('#/\.#', $uri)
) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo 'Forbidden';
    return true;
}

$publicFile = __DIR__ . '/public' . $uri;

if ($uri !== '/' && is_file($publicFile)) {
    $realPublic = realpath(__DIR__ . '/public');
    $realFile = realpath($publicFile);
    if ($realPublic === false || $realFile === false || !str_starts_with($realFile, $realPublic . DIRECTORY_SEPARATOR)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Forbidden';
        return true;
    }
    $ext = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));
    if ($ext === 'php' || $ext === 'phtml' || $ext === 'phar') {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Forbidden';
        return true;
    }
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
    }
    header('Cache-Control: public, max-age=31536000, immutable');
    header('X-Content-Type-Options: nosniff');
    readfile($publicFile);
    return true;
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
