<?php
declare(strict_types=1);

const SESSION_SECONDS = 8 * 60 * 60;
const SESSION_IDLE_SECONDS = 2 * 60 * 60;
const RATE_WINDOW_SECONDS = 15 * 60;
const RATE_MAX_ATTEMPTS = 8;
const PASSWORD_MIN_LENGTH = 10;

function start_app_session(array $config): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = is_https_request($config);
    session_name('sid');
    session_set_cookie_params([
        'lifetime' => 0, // session cookie; expiry enforced server-side
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start([
        'use_strict_mode' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => $secure,
        'gc_maxlifetime' => SESSION_SECONDS,
    ]);

    $now = time();
    if (empty($_SESSION['created_at'])) {
        $_SESSION['created_at'] = $now;
    }
    if (empty($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = $now;
    }

    $created = (int) $_SESSION['created_at'];
    $last = (int) $_SESSION['last_activity'];
    if ($now - $created > SESSION_SECONDS || $now - $last > SESSION_IDLE_SECONDS) {
        destroy_app_session();
        start_app_session($config);
        return;
    }
    $_SESSION['last_activity'] = $now;

    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}

function destroy_app_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => (bool) ($params['httponly'] ?? true),
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function regenerate_session_auth(string $username): void
{
    session_regenerate_id(true);
    $_SESSION['created_at'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    $_SESSION['user'] = $username;
    $_SESSION['flash'] = '';
}

function rotate_csrf_token(): void
{
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function session_user(): ?string
{
    $user = $_SESSION['user'] ?? null;
    return is_string($user) && $user !== '' ? $user : null;
}

function is_admin_logged_in(): bool
{
    return session_user() !== null;
}

function csrf_token(): string
{
    return (string) ($_SESSION['csrf'] ?? '');
}

function csrf_ok(string $token): bool
{
    $expected = csrf_token();
    if ($expected === '' || $token === '') {
        return false;
    }
    return hash_equals($expected, $token);
}

function origin_ok(array $config): bool
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    if (!is_string($origin) || $origin === '') {
        return true;
    }
    $parts = parse_url($origin);
    if (!is_array($parts) || empty($parts['host'])) {
        return false;
    }
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $originHost = $parts['host'];
    if (!empty($parts['port'])) {
        $originHost .= ':' . $parts['port'];
    }
    return $host !== '' && hash_equals(strtolower($host), strtolower($originHost));
}

function client_address(array $config): string
{
    if ((int) $config['trust_proxy'] === 1) {
        $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        $first = trim(explode(',', $forwarded)[0]);
        if ($first !== '' && strlen($first) <= 128 && !preg_match('/[\s\0]/', $first)) {
            return $first;
        }
    }
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function admin_auth_file(): string
{
    return app_root() . '/data/admin-auth.json';
}

/** @return array{username:string,password_hash:?string,password_plain:?string} */
function resolve_admin_credentials(array $config): array
{
    $file = admin_auth_file();
    if (is_file($file)) {
        $raw = file_get_contents($file);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($data)) {
            $user = trim((string) ($data['username'] ?? ''));
            $hash = (string) ($data['password_hash'] ?? '');
            if ($user !== '' && $hash !== '' && str_starts_with($hash, '$')) {
                return [
                    'username' => $user,
                    'password_hash' => $hash,
                    'password_plain' => null,
                ];
            }
        }
    }

    $user = (string) ($config['admin_username'] ?? 'admin');
    $hash = trim((string) ($config['admin_password_hash'] ?? ''));
    if ($hash !== '' && str_starts_with($hash, '$')) {
        return [
            'username' => $user !== '' ? $user : 'admin',
            'password_hash' => $hash,
            'password_plain' => null,
        ];
    }

    $plain = (string) ($config['admin_password'] ?? '');
    return [
        'username' => $user !== '' ? $user : 'admin',
        'password_hash' => null,
        'password_plain' => $plain,
    ];
}

function password_is_placeholder(string $password): bool
{
    return $password === '' || $password === 'ZMIEŃ-TO-HASŁO';
}

function credentials_match(array $config, string $username, string $password): bool
{
    $creds = resolve_admin_credentials($config);
    $userOk = hash_equals($creds['username'], $username);

    if ($creds['password_hash'] !== null) {
        $passOk = password_verify($password, $creds['password_hash']);
        return $userOk && $passOk;
    }

    $plain = (string) $creds['password_plain'];
    if (password_is_placeholder($plain)) {
        return false;
    }
    $passOk = hash_equals($plain, $password);
    return $userOk && $passOk;
}

/**
 * @return array{ok:bool,error:string}
 */
function update_admin_credentials(
    array $config,
    string $currentPassword,
    string $newUsername,
    string $newPassword,
    string $newPasswordConfirm
): array {
    $storedUser = resolve_admin_credentials($config)['username'];
    if (!credentials_match($config, $storedUser, $currentPassword)) {
        return ['ok' => false, 'error' => 'Obecne hasło jest nieprawidłowe.'];
    }

    $newUsername = trim($newUsername);
    if ($newUsername === '' || mb_strlen($newUsername) > 80 || !preg_match('/^[a-zA-Z0-9._@-]+$/', $newUsername)) {
        return ['ok' => false, 'error' => 'Login może zawierać litery, cyfry oraz . _ @ - (max 80 znaków).'];
    }
    if ($newPassword !== $newPasswordConfirm) {
        return ['ok' => false, 'error' => 'Nowe hasło i potwierdzenie muszą być takie same.'];
    }
    if (mb_strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        return ['ok' => false, 'error' => 'Nowe hasło musi mieć co najmniej ' . PASSWORD_MIN_LENGTH . ' znaków.'];
    }
    if (password_is_placeholder($newPassword)) {
        return ['ok' => false, 'error' => 'Wybierz inne, silne hasło.'];
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    if (!is_string($hash) || $hash === '') {
        return ['ok' => false, 'error' => 'Nie udało się zapisać hasła.'];
    }

    $dir = dirname(admin_auth_file());
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $payload = json_encode([
        'username' => $newUsername,
        'password_hash' => $hash,
        'updated_at' => gmdate('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($payload === false) {
        return ['ok' => false, 'error' => 'Nie udało się zapisać konta.'];
    }
    if (file_put_contents(admin_auth_file(), $payload, LOCK_EX) === false) {
        return ['ok' => false, 'error' => 'Brak uprawnień zapisu do katalogu data/.'];
    }

    return ['ok' => true, 'error' => ''];
}

function rate_limit_file(): string
{
    return app_root() . '/data/rate-limit.json';
}

function rate_limited(string $ip): bool
{
    $entry = rate_limit_entry($ip);
    if ($entry === null) {
        return false;
    }
    return (int) $entry['count'] >= RATE_MAX_ATTEMPTS;
}

function record_failure(string $ip): void
{
    $all = read_rate_limits();
    $now = time();
    $entry = $all[$ip] ?? null;
    if ($entry === null || $now > (int) $entry['reset_at']) {
        $all[$ip] = ['count' => 1, 'reset_at' => $now + RATE_WINDOW_SECONDS];
    } else {
        $all[$ip]['count'] = (int) $entry['count'] + 1;
    }
    write_rate_limits($all);
}

function clear_failures(string $ip): void
{
    $all = read_rate_limits();
    unset($all[$ip]);
    write_rate_limits($all);
}

function rate_limit_entry(string $ip): ?array
{
    $all = read_rate_limits();
    $entry = $all[$ip] ?? null;
    if (!is_array($entry)) {
        return null;
    }
    if (time() > (int) ($entry['reset_at'] ?? 0)) {
        unset($all[$ip]);
        write_rate_limits($all);
        return null;
    }
    return $entry;
}

function read_rate_limits(): array
{
    $file = rate_limit_file();
    if (!is_file($file)) {
        return [];
    }
    $raw = file_get_contents($file);
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function write_rate_limits(array $data): void
{
    $dir = dirname(rate_limit_file());
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    file_put_contents(rate_limit_file(), json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function take_flash(): string
{
    $message = (string) ($_SESSION['flash'] ?? '');
    $_SESSION['flash'] = '';
    return $message;
}

function set_flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function view_context(string $flash = ''): array
{
    return [
        'session_user' => session_user(),
        'csrf' => csrf_token(),
        'flash' => $flash,
    ];
}
