<?php
declare(strict_types=1);

const PRICE_SEED = [
    ['Podatkowa Księga Przychodów i Rozchodów', 'od 200 zł'],
    ['Ryczałt', 'od 200 zł'],
    ['Ewidencja VAT', '50 zł'],
    ['ZUS właściciela', '50 zł'],
    ['Kadry i Płace', '50 zł / osoba'],
    ['Księgi Handlowe', 'od 800 zł'],
];

interface PriceStore
{
    /** @return list<array{id:int,name:string,price:string,position:int}> */
    public function list(): array;

    public function create(string $name, string $price): int;

    public function update(int $id, string $name, string $price): bool;

    public function remove(int $id): bool;
}

function open_price_store(string $basePath): PriceStore
{
    $dir = dirname($basePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    if (sqlite_available()) {
        $sqlitePath = str_ends_with($basePath, '.sqlite') ? $basePath : $basePath . '.sqlite';
        return new SqlitePriceStore($sqlitePath);
    }

    $jsonPath = str_ends_with($basePath, '.json') ? $basePath : $basePath . '.json';
    return new JsonPriceStore($jsonPath);
}

function sqlite_available(): bool
{
    return class_exists('PDO')
        && in_array('sqlite', PDO::getAvailableDrivers(), true);
}

final class SqlitePriceStore implements PriceStore
{
    private PDO $pdo;

    public function __construct(string $filePath)
    {
        $this->pdo = new PDO('sqlite:' . $filePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS meta (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS price_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                price TEXT NOT NULL,
                position INTEGER NOT NULL
            )'
        );
        $this->seedIfNeeded();
    }

    private function seedIfNeeded(): void
    {
        $stmt = $this->pdo->prepare('SELECT value FROM meta WHERE key = ?');
        $stmt->execute(['seeded']);
        if ($stmt->fetch()) {
            return;
        }
        $this->pdo->beginTransaction();
        $insert = $this->pdo->prepare(
            'INSERT INTO price_items (name, price, position) VALUES (?, ?, ?)'
        );
        foreach (PRICE_SEED as $index => $row) {
            $insert->execute([$row[0], $row[1], $index + 1]);
        }
        $meta = $this->pdo->prepare('INSERT INTO meta (key, value) VALUES (?, ?)');
        $meta->execute(['seeded', '1']);
        $this->pdo->commit();
    }

    public function list(): array
    {
        $rows = $this->pdo
            ->query('SELECT id, name, price, position FROM price_items ORDER BY position ASC, id ASC')
            ->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'price' => (string) $row['price'],
                'position' => (int) $row['position'],
            ];
        }
        return $out;
    }

    public function create(string $name, string $price): int
    {
        $max = (int) $this->pdo->query('SELECT COALESCE(MAX(position), 0) FROM price_items')->fetchColumn();
        $stmt = $this->pdo->prepare(
            'INSERT INTO price_items (name, price, position) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $price, $max + 1]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $price): bool
    {
        $stmt = $this->pdo->prepare('UPDATE price_items SET name = ?, price = ? WHERE id = ?');
        $stmt->execute([$name, $price, $id]);
        return $stmt->rowCount() === 1;
    }

    public function remove(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM price_items WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() === 1;
    }
}

final class JsonPriceStore implements PriceStore
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        if (!is_file($filePath)) {
            $this->write([
                'seeded' => true,
                'next_id' => count(PRICE_SEED) + 1,
                'items' => array_map(
                    static fn (array $row, int $i): array => [
                        'id' => $i + 1,
                        'name' => $row[0],
                        'price' => $row[1],
                        'position' => $i + 1,
                    ],
                    PRICE_SEED,
                    array_keys(PRICE_SEED)
                ),
            ]);
        }
    }

    public function list(): array
    {
        $data = $this->read();
        $items = $data['items'];
        usort(
            $items,
            static function (array $a, array $b): int {
                $pos = ((int) $a['position']) <=> ((int) $b['position']);
                return $pos !== 0 ? $pos : ((int) $a['id']) <=> ((int) $b['id']);
            }
        );
        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'price' => (string) $row['price'],
                'position' => (int) $row['position'],
            ],
            $items
        );
    }

    public function create(string $name, string $price): int
    {
        $data = $this->read();
        $maxPos = 0;
        foreach ($data['items'] as $item) {
            $maxPos = max($maxPos, (int) $item['position']);
        }
        $id = (int) $data['next_id'];
        $data['items'][] = [
            'id' => $id,
            'name' => $name,
            'price' => $price,
            'position' => $maxPos + 1,
        ];
        $data['next_id'] = $id + 1;
        $this->write($data);
        return $id;
    }

    public function update(int $id, string $name, string $price): bool
    {
        $data = $this->read();
        $found = false;
        foreach ($data['items'] as &$item) {
            if ((int) $item['id'] === $id) {
                $item['name'] = $name;
                $item['price'] = $price;
                $found = true;
                break;
            }
        }
        unset($item);
        if ($found) {
            $this->write($data);
        }
        return $found;
    }

    public function remove(int $id): bool
    {
        $data = $this->read();
        $before = count($data['items']);
        $data['items'] = array_values(array_filter(
            $data['items'],
            static fn (array $item): bool => (int) $item['id'] !== $id
        ));
        if (count($data['items']) === $before) {
            return false;
        }
        $this->write($data);
        return true;
    }

    /** @return array{seeded:bool,next_id:int,items:list<array<string,mixed>>} */
    private function read(): array
    {
        $raw = file_get_contents($this->filePath);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            return ['seeded' => true, 'next_id' => 1, 'items' => []];
        }
        return [
            'seeded' => true,
            'next_id' => (int) ($data['next_id'] ?? 1),
            'items' => array_values($data['items']),
        ];
    }

    /** @param array{seeded:bool,next_id:int,items:list<array<string,mixed>>} $data */
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
