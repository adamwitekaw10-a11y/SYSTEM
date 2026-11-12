<?php
declare(strict_types=1);

function is_https_request(array $config): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }
    if ((int) $config['trust_proxy'] === 1) {
        $proto = (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
        $first = strtolower(trim(explode(',', $proto)[0]));
        return $first === 'https';
    }
    return false;
}

function security_headers(array $config): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header(
        "Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-src 'self' https://www.google.com; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'"
    );
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('X-Permitted-Cross-Domain-Policies: none');
    if (is_https_request($config)) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function send_html(int $status, string $body, array $config): void
{
    http_response_code($status);
    security_headers($config);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    echo $body;
}

function redirect_to(string $location, array $config): void
{
    http_response_code(303);
    security_headers($config);
    header('Cache-Control: no-store');
    header('Location: ' . $location);
    exit;
}

function page_error(string $title, string $message): string
{
    return '<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>' . e($title) . '</title>
  <link rel="stylesheet" href="/css/style.css?v=64">
</head>
<body>
  <main class="wrap narrow">
    <h1>' . e($title) . '</h1>
    <p>' . e($message) . '</p>
    <p><a href="/">Wróć na stronę główną</a></p>
  </main>
</body>
</html>';
}

function clean_text(mixed $value, int $max): ?string
{
    $text = preg_replace('/\s+/u', ' ', trim((string) ($value ?? '')));
    if ($text === null || $text === '' || mb_strlen($text) > $max) {
        return null;
    }
    if (preg_match('/[\x00-\x1F\x7F]/u', $text)) {
        return null;
    }
    return $text;
}

function read_post(): array
{
    $data = [
        'csrf' => (string) ($_POST['csrf'] ?? ''),
        'username' => (string) ($_POST['username'] ?? ''),
        'password' => (string) ($_POST['password'] ?? ''),
        'name' => (string) ($_POST['name'] ?? ''),
        'price' => (string) ($_POST['price'] ?? ''),
        'lead' => (string) ($_POST['lead'] ?? ''),
        'sections' => [],
    ];
    foreach (['ksiegowosc', 'kadry', 'pit'] as $key) {
        $data['sections'][$key] = [
            'title' => (string) ($_POST['title_' . $key] ?? ''),
            'html' => (string) ($_POST['html_' . $key] ?? ''),
        ];
    }
    return $data;
}

function request_path(): ?string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = parse_url($uri, PHP_URL_PATH);
    if (!is_string($path)) {
        return null;
    }
    try {
        $value = rawurldecode($path);
    } catch (Throwable) {
        return null;
    }
    if (str_contains($value, "\0")) {
        return null;
    }
    if (strlen($value) > 1 && str_ends_with($value, '/')) {
        $value = rtrim($value, '/');
    }
    return $value === '' ? '/' : $value;
}

function request_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}
