# Biuro Rachunkowe AS (PHP)

Strona Biura Rachunkowego AS pod klasyczny hosting PHP (ALTe: FTP, PHP 8.x, SQLite lub JSON). Bez Composer i bez Node.

## Wymagania

- PHP 8.0–8.3
- Rozszerzenia: `session`, `json`, `mbstring`
- Opcjonalnie `pdo_sqlite` (gdy brak — cennik/oferta w JSON w `data/`)
- Rozszerzenie `dom` / `libxml` (sanityzacja HTML oferty)
- Apache z `mod_rewrite` (produkcja) albo `php -S` (lokalnie)

## Konfiguracja

```bash
cp config.example.php config.php
# ustaw silne admin_password w config.php
```

Albo zmienne `ADMIN_PASSWORD` / `ADMIN_USERNAME` / `ADMIN_PASSWORD_HASH` (nadpisują config).

Po pierwszej zmianie hasła w `/admin/konto` hasło jest hashowane w `data/admin-auth.json` (bcrypt).

## Lokalnie

```bash
php -S 127.0.0.1:8080 router.php
```

Strona: http://127.0.0.1:8080  
Logowanie: `/logowanie` (użytkownik `admin`).

Po zalogowaniu: `/admin` (oferta, cennik, kontakt, konto). Edytor oferty: TinyMCE w `public/vendor/tinymce`.

## FTP / ALTe

Szczegóły: instrukcja wdrożenia w dokumentacji projektu (`docs/biuro-as-php-deploy.md`).

W skrócie: wrzuć zawartość paczki do `public_html`, skopiuj `config.example.php` → `config.php` z hasłem, ustaw prawa zapisu do `data/`.

## Dane

- Cennik: `data/cennik.sqlite` lub `data/cennik.json`
- Oferta: `data/oferta.sqlite` lub `data/oferta.json`
- Kontakt: `data/kontakt.sqlite` lub `data/kontakt.json`
- Auth: `data/admin-auth.json` (po zmianie hasła w panelu)
- Katalog `data/` zablokowany przez `.htaccess` / `router.php`
