# Biuro Rachunkowe AS (PHP)

Strona Biura Rachunkowego AS pod klasyczny hosting PHP (ALTe: FTP, PHP 8.x, SQLite lub JSON). Bez Composer i bez Node.

## Wymagania

- PHP 8.0–8.3
- Rozszerzenia: `session`, `json`, `mbstring`
- Opcjonalnie `pdo_sqlite` (gdy brak — cennik w `data/cennik.json`)
- Apache z `mod_rewrite` (produkcja) albo `php -S` (lokalnie)

## Konfiguracja

```bash
cp config.example.php config.php
# ustaw admin_password w config.php
```

Albo zmienna środowiskowa `ADMIN_PASSWORD` (nadpisuje config).

## Lokalnie

```bash
php -S 127.0.0.1:8080 router.php
```

Strona: http://127.0.0.1:8080  
Logowanie: `/logowanie` (użytkownik `admin`).

## FTP / ALTe

Szczegóły: instrukcja wdrożenia w dokumentacji projektu (`docs/biuro-as-php-deploy.md`).

W skrócie: wrzuć zawartość paczki do `public_html`, skopiuj `config.example.php` → `config.php` z hasłem, ustaw prawa zapisu do `data/`.

## Cennik

- SQLite: `data/cennik.sqlite` (gdy PDO_sqlite dostępne)
- JSON: `data/cennik.json` (fallback)
- Katalog `data/` zablokowany przez `.htaccess`
