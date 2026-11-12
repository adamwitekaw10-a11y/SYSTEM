<?php
declare(strict_types=1);

interface ContactStore
{
    /** @return array<string,string> */
    public function get(): array;

    /** @param array<string,string> $data */
    public function save(array $data): void;
}

function contact_defaults(): array
{
    return [
        'phone' => '18 541 22 17',
        'phone_mobile' => '530 770 208',
        'email' => 'biuro@biuro-as.pl',
        'street' => 'ul. Bora-Komorowskiego 22/1',
        'street_footer' => 'ul. Bora-Komorowskiego 22 lok. 1',
        'postal_city' => '33-300 Nowy Sącz',
        'city_short' => 'Nowy Sącz',
        'region' => 'woj. Małopolskie',
        'hours' => 'Poniedziałek – Piątek: 09:00 – 16:00',
        'hours_opens' => '09:00',
        'hours_closes' => '16:00',
        'owner' => 'Aneta Stanik',
    ];
}

function contact_tel_href(string $display): string
{
    $digits = preg_replace('/\D+/', '', $display) ?? '';
    if ($digits === '') {
        return '#';
    }
    if (str_starts_with($digits, '48')) {
        return 'tel:+' . $digits;
    }
    if (strlen($digits) === 9 || strlen($digits) === 10) {
        return 'tel:+48' . $digits;
    }
    return 'tel:+' . $digits;
}

function normalize_contact_payload(array $input): array
{
    $defaults = contact_defaults();
    $out = [];
    foreach ($defaults as $key => $default) {
        $max = match ($key) {
            'email' => 160,
            'hours', 'street', 'street_footer', 'postal_city' => 160,
            'region', 'owner', 'city_short' => 80,
            'hours_opens', 'hours_closes' => 8,
            default => 40,
        };
        $value = clean_text($input[$key] ?? '', $max);
        $out[$key] = $value ?? $default;
    }
    if (!filter_var($out['email'], FILTER_VALIDATE_EMAIL)) {
        $out['email'] = $defaults['email'];
    }
    return $out;
}

function open_contact_store(string $basePath): ContactStore
{
    $dir = dirname($basePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    if (sqlite_available()) {
        $sqlitePath = str_ends_with($basePath, '.sqlite') ? $basePath : $basePath . '.sqlite';
        return new SqliteContactStore($sqlitePath);
    }
    $jsonPath = str_ends_with($basePath, '.json') ? $basePath : $basePath . '.json';
    return new JsonContactStore($jsonPath);
}

function resolve_contact_data_path(array $config): string
{
    if ($config['data_path'] !== '') {
        return dirname($config['data_path']) . '/kontakt';
    }
    return app_root() . '/data/kontakt';
}

/** @var array<string,string>|null */
$SITE_CONTACT = null;

function set_site_contact(array $data): void
{
    global $SITE_CONTACT;
    $SITE_CONTACT = normalize_contact_payload($data);
}

/** @return array<string,string> */
function site_contact(): array
{
    global $SITE_CONTACT;
    return is_array($SITE_CONTACT) ? $SITE_CONTACT : contact_defaults();
}

final class SqliteContactStore implements ContactStore
{
    private PDO $pdo;

    public function __construct(string $filePath)
    {
        $this->pdo = new PDO('sqlite:' . $filePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS contact_meta (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            )'
        );
        $this->seedIfNeeded();
    }

    private function seedIfNeeded(): void
    {
        $stmt = $this->pdo->prepare('SELECT value FROM contact_meta WHERE key = ?');
        $stmt->execute(['seeded']);
        if ($stmt->fetch()) {
            return;
        }
        $this->pdo->beginTransaction();
        $insert = $this->pdo->prepare(
            'INSERT INTO contact_meta (key, value) VALUES (?, ?)'
        );
        $insert->execute(['seeded', '1']);
        foreach (contact_defaults() as $key => $value) {
            $insert->execute([$key, $value]);
        }
        $this->pdo->commit();
    }

    public function get(): array
    {
        $rows = $this->pdo->query('SELECT key, value FROM contact_meta')->fetchAll();
        $data = contact_defaults();
        foreach ($rows as $row) {
            $key = (string) $row['key'];
            if ($key === 'seeded' || !array_key_exists($key, $data)) {
                continue;
            }
            $data[$key] = (string) $row['value'];
        }
        return normalize_contact_payload($data);
    }

    public function save(array $data): void
    {
        $normalized = normalize_contact_payload($data);
        $this->pdo->beginTransaction();
        $upsert = $this->pdo->prepare(
            'INSERT INTO contact_meta (key, value) VALUES (?, ?)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value'
        );
        $upsert->execute(['seeded', '1']);
        foreach ($normalized as $key => $value) {
            $upsert->execute([$key, $value]);
        }
        $this->pdo->commit();
    }
}

final class JsonContactStore implements ContactStore
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        if (!is_file($filePath)) {
            $this->write(contact_defaults());
        }
    }

    public function get(): array
    {
        $raw = file_get_contents($this->filePath);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        return normalize_contact_payload(is_array($data) ? $data : []);
    }

    public function save(array $data): void
    {
        $this->write(normalize_contact_payload($data));
    }

    /** @param array<string,string> $data */
    private function write(array $data): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        file_put_contents(
            $this->filePath,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }
}
