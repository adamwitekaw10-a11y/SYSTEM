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

Jeśli `ADMIN_PASSWORD` nie jest ustawione, serwer używa lokalnego hasła deweloperskiego. Ta wartość nie jest pokazywana w interfejsie i nie nadaje się do serwera dostępnego publicznie.

Pozycje cennika trafiają do pliku `data/cennik.sqlite` (katalog jest w `.gitignore`).

Za odwrotnym proxy HTTPS ustaw `TRUST_PROXY=1`, żeby ciasteczko sesji dostało flagę `Secure`.

## Testy

```bash
npm test
```
