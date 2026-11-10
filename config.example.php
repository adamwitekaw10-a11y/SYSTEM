<?php
/**
 * Skopiuj ten plik jako config.php i uzupełnij hasło.
 * config.php NIE wolno wrzucać do publicznego repozytorium.
 *
 * Alternatywnie (np. lokalnie) możesz ustawić zmienne środowiskowe:
 *   ADMIN_PASSWORD, ADMIN_USERNAME, SITE_URL, TRUST_PROXY, DATA_PATH
 */
declare(strict_types=1);

return [
    // Nazwa użytkownika do edycji cennika (domyślnie admin)
    'admin_username' => 'admin',

    // Hasło — ustaw własne, silne. Bez hasła logowanie nie zadziała.
    'admin_password' => 'ZMIEŃ-TO-HASŁO',

    // Canonical / Open Graph (bez końcowego /)
    'site_url' => 'https://www.biuro-as.pl',

    // 1 jeśli serwis stoi za HTTPS reverse proxy (ALTe zwykle tak przy SSL)
    'trust_proxy' => 1,

    // Opcjonalna ścieżka do pliku danych (sqlite lub json). Puste = data/ w katalogu aplikacji.
    'data_path' => '',
];
