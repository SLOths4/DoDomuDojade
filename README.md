# DoDomuDojadę

[![GitHub last commit](https://img.shields.io/github/last-commit/SLOths4/DoDomuDojade)](https://github.com/SLOths4/DoDomuDojade/commits/main)  
[![GitHub issues](https://img.shields.io/github/issues/SLOths4/DoDomuDojade)](https://github.com/SLOths4/DoDomuDojade/issues)  
[![GitHub stars](https://img.shields.io/github/stars/SLOths4/DoDomuDojade?style=social)](https://github.com/SLOths4/DoDomuDojade/stargazers)

## O projekcie

DoDomuDojadę to aplikacja webowa, która stanowi wirtualną tablicę informacyjną.

**Cele projektu:**
- Ułatwienie dostępu do informacji publicznych (transport, pogoda).
- Minimalistyczna architektura dla szybkiego developmentu.
- Łatwość integracji z nowymi źródłami danych.

## Źródła danych

Aplikacja jest przystosowana do korzystania z poniższych źródeł danych:
- [IMGW](https://www.imgw.pl/) – dane pogodowe.
- [Airly](https://airly.org/) – jakość powietrza.
- ZTM – odjazdy pojazdów komunikacji miejskiej.
- Baza danych: sqlite (konfiguracja PDO w .env).

Uwaga: Wszystkie źródła są publiczne, ale wymagają kluczy API dla niektórych endpointów. Monitoruj limity zapytań, aby uniknąć blokad.

## Spis treści

- [Wymagania](#wymagania)
- [Stos technologiczny](#stos-technologiczny)
- [Szybki start](#szybki-start)
- [Konfiguracja środowiska](#konfiguracja-środowiska)
- [Uruchamianie aplikacji](#uruchamianie-aplikacji)
- [Rozwiązywanie problemów](#rozwiązywanie-problemów)
- [FAQ](#faq)

## Wymagania

- **PHP**: 8.4 (zalecane; minimum 8.1), rozszerzenia: PDO (do bazy danych), mbstring (obsługa ciągów), intl (opcjonalnie dla formatowania), json (standardowe).
- **Composer**: 2.x do zarządzania zależnościami backendu.
- **Node.js i npm**: 18+ i 9+ do buildów frontendu (Tailwind CSS).
- **Baza danych**: sqlite lub inna zgodna z PDO.
- **Uprawnienia**: Baza danych oraz katalog `logs/` muszą być zapisywalne przez serwer PHP.

## Stos technologiczny

### Backend
- PHP 8.4 (core języka, bez frameworka jak Laravel/Symfony dla lekkości).
- Routing: nikic/fast-route v1.3.0.
- Logowanie: monolog/monolog v3.9.0, psr/log v3.0.2.
- HTTP klient: symfony/http-client v7.2.4 (do zapytań API).
- Konfiguracja: vlucas/phpdotenv v5.6.2 (ładowanie .env).
- Inne: graham-campbell/result-type, phpoption/phpoption, psr/container, symfony/*-contracts (polyfills dla kompatybilności).
- Testy: PHPUnit (konfigurowane w phpunit.xml).

### Frontend
- Tailwind CSS 3.4.17 (utility-first CSS).
- Alipne.js

## Szybki start

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

4) Uruchom backend lokalnie (przykłady):
```shell script
php -S localhost:8080 -t public/ public/router.php
```
3. Skonfiguruj zmienne środowiskowe:
   ```
   cp .env.example .env
   # Edytuj .env i ustaw brakujące wartości
   ```

4. Zbuduj frontend (jeśli dotyczy):
   ```
   npm run build
   ```

## Konfiguracja środowiska

Plik .env (ładowany przez vlucas/phpdotenv) powinien być zgodny z .env.example. 

## Uruchamianie aplikacji

- **Lokalnie**: Użyj wbudowanego serwera PHP: `php -S localhost:8000 -t public`.
- **Produkcyjnie**:
    - Ustaw document root na `public/` (np. w Apache/Nginx).
    - Włącz OPcache w php.ini dla wydajności.
    - Ustaw `APP_DEBUG=false` i `LOG_LEVEL=warning`.
    - Konfiguruj PHP-FPM z pulą workerów (np. 5-10 dla małego obciążenia).
    - Upewnij się, że `logs/` jest zapisywalne, ale nie publiczne.
    - Zablokuj dostęp do katalogów źródłowych oraz .env.

## Rozwiązywanie problemów

- **Błąd 500**: Sprawdź logi (`logs/app.log`); włącz `APP_DEBUG=true`.
- **Brak danych API**: Weryfikuj klucze w .env; testuj curl-em.
- **CSS nie ładuje**: Uruchom `npm run build`; sprawdź ścieżki w HTML.
- **Baza nie łączy**: Sprawdź PDO exceptions; testuj połączenie w teście.

## FAQ

- **Dlaczego projekt nie używa frameworka?** Głownym celem jest lekkość projektu oraz nauka PHP.

## Autorzy

© SLOths4 2025
- Franciszek Kruszewski [@Kruszewski](https://github.com/Kruszewski)
- Igor Woźnica [@hexer7](https://github.com/hexer7)
