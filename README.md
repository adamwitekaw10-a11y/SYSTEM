# Biuro Rachunkowe AS

Nowa strona biura: te same informacje, co na [biuro-as.pl](https://www.biuro-as.pl/), w prostszym układzie. Cennik mogą zmieniać tylko zalogowane osoby.

## Uruchomienie

Wymagany jest Node.js 22.

```bash
npm start
```

Strona działa pod adresem `http://127.0.0.1:3000`.

## Hasło administratora

Hasło nie jest zapisane w kodzie. Ustaw zmienną środowiskową `ADMIN_PASSWORD` przed startem:

```bash
ADMIN_PASSWORD='twoje-haslo' npm start
```

Nazwa użytkownika to `admin`. Logowanie: `/logowanie`.

Bez `ADMIN_PASSWORD` serwer **nie startuje** (jasny komunikat w konsoli).

Pozycje cennika trafiają do pliku `data/cennik.sqlite` (katalog jest w `.gitignore`).

Za odwrotnym proxy HTTPS ustaw `TRUST_PROXY=1`, żeby ciasteczko sesji dostało flagę `Secure`, a rate-limit logowania brał IP z `X-Forwarded-For`.

Opcjonalnie `SITE_URL` (domyślnie `https://www.biuro-as.pl`) ustawia canonical / Open Graph.

## Testy

```bash
npm test
```
