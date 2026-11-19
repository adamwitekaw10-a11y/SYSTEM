<?php
declare(strict_types=1);

const OFFER_SECTION_KEYS = ['ksiegowosc', 'kadry', 'pit'];

interface OfferStore
{
    /** @return array{lead:string,sections:array<string,array{title:string,html:string}>} */
    public function get(): array;

    /** @param array{lead:string,sections:array<string,array{title:string,html:string}>} $data */
    public function save(array $data): void;
}

function offer_defaults(): array
{
    return [
        'lead' => 'Nasze usługi:',
        'sections' => [
            'ksiegowosc' => [
                'title' => 'Księgowość',
                'html' => '<p>Nasza oferta skierowana jest głównie do małych i średnich przedsiębiorstw. Uwalniamy Państwa od ciężaru prowadzenia księgowości, gwarantując dyskrecję, terminowość, sumienność i rzetelność zgodnie z obowiązującymi przepisami. Przejmując na siebie ciężar kontaktów z urzędami oszczędzamy Państwa czas i pieniądze.</p>
<p>Biuro Rachunkowe AS oferuje kompleksową obsługę księgową dla małych i średnich podmiotów gospodarczych o różnym profilu działalności i formie prawnej.</p>
<p>Świadczymy profesjonalne usługi księgowe w zakresie:</p>
<ul>
  <li>prowadzenia pełnej księgowości (Księgi Handlowe),</li>
  <li>prowadzenia księgowości uproszczonej (Podatkowe Księgi Przychodów i Rozchodów, Ryczałt),</li>
  <li>Ewidencji środków trwałych i wyposażenia.</li>
  <li>Ewidencji zakupów i sprzedaży dla potrzeb podatku VAT,</li>
  <li>Przygotowania deklaracji podatkowych PIT, VAT, CIT, PFRON i innych,</li>
  <li>Przygotowania deklaracji dla ZUS i ich przesyłu elektronicznego,</li>
  <li>Sporządzania rocznych sprawozdań finansowych,</li>
  <li>Przygotowania rocznych rozliczeń podatkowych,</li>
  <li>Realizowanie indywidualnych potrzeb Klienta z zakresu księgowości,</li>
</ul>
<p>W ramach kompleksowej obsługi Biuro Rachunkowe AS zapewnia Klientom:</p>
<ul>
  <li>reprezentowanie ich przed urzędami skarbowymi, ZUS, KRS, GUS, PFRON</li>
  <li>bezpłatną pomoc w założeniu działalności gospodarczej osobom fizycznym (wypełnianiu deklaracji zgłoszeniowych, wniosków rejestracyjnych)</li>
  <li>możliwość łączności z nami za pośrednictwem serwera</li>
  <li>dużą dyspozycyjność i indywidualne podejście do każdego klienta.</li>
</ul>
<p>Biuro przejmuje odpowiedzialność za terminowe przygotowanie wszelkich niezbędnych dokumentów do rozliczeń z ZUS-em, Urzędem Skarbowym, GUS-em, PFRON-em.</p>',
            ],
            'kadry' => [
                'title' => 'Kadry i płace',
                'html' => '<p>Dla tych z Państwa , którzy całość spraw związanych prowadzeniem kadr i płac chcą przekazać do obsługi przez biuro rachunkowe możemy zaoferować:</p>
<ul>
  <li>kompleksową obsługę kadrowo-płacową,</li>
  <li>prowadzenie kart wynagrodzeń</li>
  <li>przygotowywanie dokumentów związanych z zatrudnieniem i rozwiązaniem stosunku pracy,</li>
  <li>prowadzenie akt osobowych pracowników,</li>
  <li>prowadzenie ewidencji badań wstępnych, okresowych, kontrolnych,</li>
  <li>ewidencjonowanie czasu pracy i nieobecności,</li>
  <li>obsługa zatrudnienia na podstawie umów cywilnoprawnych (przygotowywanie umów, rachunków),</li>
  <li>sporzadzenie list płac,</li>
  <li>sporządzanie i przekazywanie deklaracji rozliczeniowych do ZUS i US, PFRON</li>
  <li>sporzadzenie deklaracji i zaświadczeń dla pracowników,</li>
  <li>obliczanie należnych składek ubezpieczeniowych, zaliczek na podatek od wynagrodzeń i umów cywilnoprawnych,</li>
</ul>
<p>Usługi kadrowo-płacowe prowadzimy przy wsparciu licencjonowanych systemów informatycznych.</p>',
            ],
            'pit' => [
                'title' => 'Rozliczenia PIT',
                'html' => '<p><strong>Rozliczanie dochodów za 2023 rok – zeznania roczne PIT-36, PIT-36L PIT-37 oraz PIT-28</strong></p>
<div class="table-scroll">
  <table>
    <thead>
      <tr>
        <th scope="col"></th>
        <th scope="col">Rozliczenie jednej osoby</th>
        <th scope="col">Rozliczenie wspólne małżonków<br>Rozliczenie wspólne z dzieckiem</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <th scope="row">Jeden dokument potwierdzający źródło dochodów – PIT-11</th>
        <td>60,00 zł</td>
        <td>80,00 zł</td>
      </tr>
      <tr>
        <th scope="row">PIT-36 / PIT-28 działalność gospodarcza, praca za granicą</th>
        <td>120,00 zł</td>
        <td>120,00 zł</td>
      </tr>
    </tbody>
  </table>
</div>
<p class="note">Podane ceny są cenami brutto.</p>
<p>Oferta dla naszych klientów:</p>
<ul>
  <li>jeżeli zeznanie roczne opiera się tylko na wynikach z działalności gospodarczej, którą rozliczamy i nie występują żadne doliczenia, odliczenia i ulgi, to wówczas sporządzamy je w ramach obsługi księgowej firmy, bez żadnych dodatkowych opłat</li>
  <li>w pozostałych przypadkach obowiązują powyższe ceny pomniejszone o 10,00 zł</li>
</ul>',
            ],
        ],
    ];
}

function open_offer_store(string $basePath): OfferStore
{
    $dir = dirname($basePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    if (sqlite_available()) {
        $sqlitePath = str_ends_with($basePath, '.sqlite') ? $basePath : $basePath . '.sqlite';
        return new SqliteOfferStore($sqlitePath);
    }

    $jsonPath = str_ends_with($basePath, '.json') ? $basePath : $basePath . '.json';
    return new JsonOfferStore($jsonPath);
}

function resolve_offer_data_path(array $config): string
{
    if ($config['data_path'] !== '') {
        $dir = dirname($config['data_path']);
        return $dir . '/oferta';
    }
    return app_root() . '/data/oferta';
}

function normalize_offer_payload(array $input): array
{
    $defaults = offer_defaults();
    $lead = clean_text($input['lead'] ?? '', 200);
    if ($lead === null) {
        $lead = $defaults['lead'];
    }

    $sections = [];
    foreach (OFFER_SECTION_KEYS as $key) {
        $default = $defaults['sections'][$key];
        $title = clean_text($input['sections'][$key]['title'] ?? '', 120);
        if ($title === null) {
            $title = $default['title'];
        }
        $html = sanitize_offer_html((string) ($input['sections'][$key]['html'] ?? ''));
        if ($html === '') {
            $html = $default['html'];
        }
        $sections[$key] = [
            'title' => $title,
            'html' => $html,
        ];
    }

    return [
        'lead' => $lead,
        'sections' => $sections,
    ];
}

final class SqliteOfferStore implements OfferStore
{
    private PDO $pdo;

    public function __construct(string $filePath)
    {
        $this->pdo = new PDO('sqlite:' . $filePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS offer_meta (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS offer_sections (
                id TEXT PRIMARY KEY,
                title TEXT NOT NULL,
                html TEXT NOT NULL
            )'
        );
        $this->seedIfNeeded();
    }

    private function seedIfNeeded(): void
    {
        $stmt = $this->pdo->prepare('SELECT value FROM offer_meta WHERE key = ?');
        $stmt->execute(['seeded']);
        if ($stmt->fetch()) {
            return;
        }
        $defaults = offer_defaults();
        $this->pdo->beginTransaction();
        $meta = $this->pdo->prepare('INSERT INTO offer_meta (key, value) VALUES (?, ?)');
        $meta->execute(['seeded', '1']);
        $meta->execute(['lead', $defaults['lead']]);
        $insert = $this->pdo->prepare(
            'INSERT INTO offer_sections (id, title, html) VALUES (?, ?, ?)'
        );
        foreach ($defaults['sections'] as $id => $section) {
            $insert->execute([$id, $section['title'], $section['html']]);
        }
        $this->pdo->commit();
    }

    public function get(): array
    {
        $defaults = offer_defaults();
        $leadStmt = $this->pdo->prepare('SELECT value FROM offer_meta WHERE key = ?');
        $leadStmt->execute(['lead']);
        $leadRow = $leadStmt->fetch();
        $lead = $leadRow ? (string) $leadRow['value'] : $defaults['lead'];

        $rows = $this->pdo->query('SELECT id, title, html FROM offer_sections')->fetchAll();
        $sections = $defaults['sections'];
        foreach ($rows as $row) {
            $id = (string) $row['id'];
            if (!in_array($id, OFFER_SECTION_KEYS, true)) {
                continue;
            }
            $sections[$id] = [
                'title' => (string) $row['title'],
                'html' => sanitize_offer_html((string) $row['html']),
            ];
        }

        return ['lead' => $lead, 'sections' => $sections];
    }

    public function save(array $data): void
    {
        $normalized = normalize_offer_payload($data);
        $this->pdo->beginTransaction();
        $meta = $this->pdo->prepare(
            'INSERT INTO offer_meta (key, value) VALUES (?, ?)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value'
        );
        $meta->execute(['lead', $normalized['lead']]);
        $meta->execute(['seeded', '1']);
        $upsert = $this->pdo->prepare(
            'INSERT INTO offer_sections (id, title, html) VALUES (?, ?, ?)
             ON CONFLICT(id) DO UPDATE SET title = excluded.title, html = excluded.html'
        );
        foreach ($normalized['sections'] as $id => $section) {
            $upsert->execute([$id, $section['title'], $section['html']]);
        }
        $this->pdo->commit();
    }
}

final class JsonOfferStore implements OfferStore
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        if (!is_file($filePath)) {
            $this->write(offer_defaults());
        }
    }

    public function get(): array
    {
        $raw = file_get_contents($this->filePath);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            return offer_defaults();
        }
        return normalize_offer_payload([
            'lead' => (string) ($data['lead'] ?? ''),
            'sections' => is_array($data['sections'] ?? null) ? $data['sections'] : [],
        ]);
    }

    public function save(array $data): void
    {
        $this->write(normalize_offer_payload($data));
    }

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
