<?php
/**
 * Skopiuj ten plik jako config.php i uzupełnij hasło.
 * config.php NIE wolno wrzucać do publicznego repozytorium.
 *
 * Alternatywnie (np. lokalnie) możesz ustawić zmienne środowiskowe:
 *   ADMIN_PASSWORD, ADMIN_USERNAME, ADMIN_PASSWORD_HASH, SITE_URL, TRUST_PROXY, DATA_PATH
 *
 * Po pierwszej zmianie hasła w panelu (/admin/konto) dane trafiają do
 * data/admin-auth.json (hash bcrypt) i nadpisują wartości z config.php.
 */
declare(strict_types=1);

return [
    // Nazwa użytkownika do edycji treści (domyślnie admin)
    'admin_username' => 'admin',

    // Hasło plaintext — tylko do pierwszego uruchomienia. Ustaw własne, silne.
    // Po zmianie w /admin/konto używany jest wyłącznie hash w data/admin-auth.json.
    'admin_password' => 'ZMIEŃ-TO-HASŁO',

    // Opcjonalnie: gotowy hash z password_hash() zamiast plaintextu powyżej.
    // Gdy ustawione (zaczyna się od $), admin_password jest ignorowane.
    'admin_password_hash' => '',

    // Canonical / Open Graph (bez końcowego /)
    'site_url' => 'https://www.biuro-as.pl',

    // 1 jeśli serwis stoi za HTTPS reverse proxy (ALTe zwykle tak przy SSL)
    'trust_proxy' => 1,

    // Opcjonalna ścieżka do pliku danych (sqlite lub json). Puste = data/ w katalogu aplikacji.
    'data_path' => '',

    // Embed darmowego widgetu opinii Google (np. Trustindex). Puste = czytaj data/google-reviews.html.
    // Alternatywa: env GOOGLE_REVIEWS_EMBED. Nie wklejaj fikcyjnych opinii — tylko oficjalny kod widgetu.
    // Przykład (to samo co w data/google-reviews.html):
    // 'google_reviews_embed' => '<script defer async src="https://cdn.trustindex.io/loader.js?2c55db586bcf14953016122a798"></script>',
    'google_reviews_embed' => '',
];
