# DoDomuDojadę

[![GitHub last commit](https://img.shields.io/github/last-commit/SLOths4/DoDomuDojade)](https://github.com/SLOths4/DoDomuDojade/commits/main)  
[![GitHub issues](https://img.shields.io/github/issues/SLOths4/DoDomuDojade)](https://github.com/SLOths4/DoDomuDojade/issues)  
[![GitHub stars](https://img.shields.io/github/stars/SLOths4/DoDomuDojade?style=social)](https://github.com/SLOths4/DoDomuDojade/stargazers)

## O projekcie

DoDomuDojadę to aplikacja webowa, która stanowi wirtualną tablicę informacyjną.

**Cele projektu:**
- Ułatwienie dostępu do informacji publicznych (transport, pogoda).
- Minimalistyczna architektura dla szybkiego development-u.
- Łatwość integracji z nowymi źródłami danych.

## Spis treści

- [Dostępne moduły](#Dostępne-moduły)
- [Stos technologiczny](#stos-technologiczny)
- [Szybki start (dev)](#Szybki-start-dev)
- [Szybki start (prod)](#Szybki-start-produkcja)
- [Rozwiązywanie problemów](#rozwiązywanie-problemów)
- [FAQ](#faq)
- [Autorzy](#Autorzy)

## Dostępne moduły
- **tramwaje**
- **ogłoszenia**
- **słowo dnia**
- **cytat dnia**
- **pogoda**
- **odliczanie**

## Stos technologiczny
### Backend
- PHP 8.4
### Frontend
- Tailwind CSS
- Alpine.js
### Baza danych
- SQlite3 lub Postegres

## Uruchamianie aplikacji

### Szybki start (dev)

1. Sklonuj repozytorium:
   ```
   git clone https://github.com/SLOths4/DoDomuDojade.git
   cd DoDomuDojade
   ```
2. Zainstaluj zależności:
   ```
   composer install
   npm ci
   ```
3. Uruchom backend lokalnie (przykłady):
    ```shell script
    php -S localhost:8080 -t public/ public/router.php
    ```
4. Skonfiguruj zmienne środowiskowe:
   ```
   cp .env.example .env
   ```
5. Zbuduj frontend:
   ```
   npm run dev
   ```

### Szybki start (produkcja)
- Ustaw document root na `public/` (np. w Apache/Nginx).
- Ustaw `APP_ENV=prod` i `LOGGING_LEVEL=info`.
- Konfiguruj PHP-FPM.
- Upewnij się, że `logs/` jest zapisywalne, ale nie publiczne.
- Zablokuj dostęp do katalogów źródłowych oraz .env.
- Ustaw cron jobs `bin/console.php` z argumentem `word:fetch` `quote:fetch`

## Rozwiązywanie problemów

- **Błąd 500**: Sprawdź logi (`logs/app-{YYYY-MM-DD}.log`); sprawdź logi php fmp; włącz `APP_ENV=dev`.
- **Brak danych API**: Weryfikuj klucze w .env.
- **CSS nie ładuje**: Uruchom `npm run build`; sprawdź ścieżki w HTML.
- **Baza nie łączy**: Sprawdź PDO exceptions; testuj połączenie w teście.
- **Błąd bazy danych**: Sprawdź, czy baza danych ma prawidłową strukturę i zawiera wszystkie tabele; sprawdź, czy 

## FAQ

- **Dlaczego projekt nie używa framework-a?** Głównym celem jest lekkość projektu oraz nauka PHP.

## Licencja

Ten projekt jest licencjonowany na warunkach **CC-BY-NC-4.0** — [pełny tekst licencji](LICENSE).

Oznacza to:
- ✅ Możesz używać, modyfikować, dzielić się
- ✅ Musisz przypisać autorstwo
- ❌ Nie możesz użytkować komercyjnie bez zgody

## Autorzy

© **SLOths4** 2025

| Autor | GitHub | Rola |
|-------|--------|------|
| Franciszek Kruszewski | [@Kruszewski](https://github.com/Kruszewski) | Full-stack development, architecture |
| Igor Woźnica | [@hexer7](https://github.com/hexer7) | Frontend, UI/UX |

---
**Masz pytania?** Otwórz [Issue](https://github.com/SLOths4/DoDomuDojade/issues) lub skontaktuj się z nami na [sloths4@spolecznaczworka.pl](mailto:sloths4@spolecznaczworka.pl).