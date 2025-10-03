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
- [Google Calendar](https://calendar.google.com/) – kalendarz wydarzeń.
- ZTM (PEKA) – odjazdy komunikacji miejskiej.
- Baza danych: sqlite (konfiguracja PDO w .env).

Uwaga: Wszystkie źródła są publiczne, ale wymagają kluczy API dla niektórych endpointów. Monitoruj limity zapytań, aby uniknąć blokad.

## Spis treści

- [Wymagania](#wymagania)
- [Stos technologiczny](#stos-technologiczny)
- [Szybki start](#szybki-start)
- [Konfiguracja środowiska](#konfiguracja-środowiska)
- [Uruchamianie aplikacji](#uruchamianie-aplikacji)
- [Testy](#testy)
- [Logowanie i diagnostyka](#logowanie-i-diagnostyka)
- [Styl kodu i jakość](#styl-kodu-i-jakość)
- [Frontend i CSS](#frontend-i-css)
- [Struktura projektu](#struktura-projektu)
- [Wersjonowanie i wydania](#wersjonowanie-i-wydania)
- [Kontrybucje](#kontrybucje)
- [Bezpieczeństwo](#bezpieczeństwo)
- [Rozwiązywanie problemów](#rozwiązywanie-problemów)
- [FAQ](#faq)

## Wymagania

- **PHP**: 8.4 (zalecane; minimum 8.1), rozszerzenia: PDO (do bazy danych), mbstring (obsługa ciągów), intl (opcjonalnie dla formatowania), json (standardowe).
- **Composer**: 2.x do zarządzania zależnościami backendu.
- **Node.js i npm**: 18+ i 9+ do buildów frontendu (Tailwind CSS).
- **Baza danych**: sqlite lub inna zgodna z PDO.
- **Uprawnienia**: Katalogi `cache/` i `logs/` muszą być zapisywalne przez serwer PHP.
- **Środowisko deweloperskie**: Git, edytor kodu (np. VS Code z rozszerzeniami PHP Intelephense i Tailwind CSS IntelliSense).

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
- PostCSS 8.5.3 z Autoprefixer 10.4.20 (do przetwarzania CSS).
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

3. Skonfiguruj zmienne środowiskowe:
   ```
   cp .env.example .env
   # Edytuj .env i ustaw brakujące wartości
   ```

4. Zbuduj frontend (jeśli dotyczy):
   ```
   npx tailwindcss -c tailwind.config.js -i ./resources/css/app.css -o ./public/assets/app.css --minify
   ```
   Lub użyj skryptów npm: `npm run build`.

5. Uruchom backend lokalnie:
   ```
   php -S localhost:8000 -t public
   ```
   Otwórz w przeglądarce: http://localhost:8000.

## Konfiguracja środowiska

Plik .env (ładowany przez vlucas/phpdotenv) powinien zawierać kluczowe zmienne. Przykładowy zestaw:

```
# Airly data
AIRLY_API_KEY=
AIRLY_LOCATION_ID=
AIRLY_ENDPOINT=https://airapi.airly.eu/v2/measurements/location?locationId=

# Calendar
CALENDAR_URL=

# IMGW
IMGW_WEATHER_URL=

# DATABASE
DB_HOST=localhost
DB_USERNAME=""
DB_PASSWORD=""

# ZTM
ZTM_URL=https://www.peka.poznan.pl/vm/method.vm
```

Ładuj konfigurację w `index.php` za pomocą `Dotenv::createImmutable(__DIR__)->load();`.

## Uruchamianie aplikacji

- **Lokalnie**: Użyj wbudowanego serwera PHP: `php -S localhost:8000 -t public`.
- **Produkcyjnie**:
    - Ustaw document root na `public/` (np. w Apache/Nginx).
    - Włącz OPcache w php.ini dla wydajności.
    - Ustaw `APP_DEBUG=false` i `LOG_LEVEL=warning`.
    - Konfiguruj PHP-FPM z pulą workerów (np. 5-10 dla małego obciążenia).
    - Upewnij się, że `logs/` jest zapisywalne, ale nie publiczne.
    - Zablokuj dostęp do katalogów źródłowych oraz .env.

## Testy

Projekt używa PHPUnit do testów jednostkowych i integracyjnych.

- **Instalacja**: Zależności testowe są w composer.json (phpunit/phpunit).
- **Uruchamianie**:
  ```
  vendor/bin/phpunit  # Wszystkie testy
  vendor/bin/phpunit tests/Unit/ExampleTest.php  # Pojedynczy plik
  ```
- **Konfiguracja**: Plik `phpunit.xml` definiuje bootstrap, coverage i suity testowe.
- **Przykładowy test** (dodaj do `tests/Unit/DatabaseTest.php`):
  ```php
  <?php

  namespace Tests\Unit;

  use PHPUnit\Framework\TestCase;
  use PDO;

  class DatabaseTest extends TestCase
  {
      public function testDatabaseConnection()
      {
          $pdo = new PDO('mysql:host=' . getenv('DB_HOST'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
          $this->assertInstanceOf(PDO::class, $pdo);
      }
  }
  ```
- **Najlepsze praktyki**: Pokryj testami modele (np. zapytania PDO), kontrolery (routing) i serwisy (logika API). Celuj w >80% coverage. Używaj mocks dla zewnętrznych API (np. z symfony/http-client).

## Logowanie i diagnostyka

- Monolog zapisuje logi zgodnie z LOG_PATH i LOG_LEVEL.
- Poziomy: debug (lokalnie), info, warning, error (prod).
- Przykładowe użycie w kodzie:
  ```php
  use Monolog\Logger;
  use Monolog\Handler\StreamHandler;

  $logger = new Logger('app');
  $logger->pushHandler(new StreamHandler(__DIR__.'/logs/app.log', Logger::DEBUG));
  $logger->info('Request processed');
  ```
- Diagnostyka: Używaj `var_dump()` lub Xdebug w dev; na prod sprawdzaj logi via `tail -f logs/app.log`.

## Styl kodu i jakość

- Standard: PSR-12, typowanie w PHP 8.4, unikanie nadmiernych wyjątków w kontrolach przepływu.
- **Typowanie**: Używaj strict types (`declare(strict_types=1);`) i type hints w PHP 8+.
- **Jakość**: Unikaj globali, preferuj dependency injection. Używaj Rector/PHPStan do refaktoringu (dodaj jako dev-dependencies).
- **Linting**: Dla PHP: `composer require --dev friendsofphp/php-cs-fixer` i skonfiguruj `.php-cs-fixer.php`. Uruchamiaj: `vendor/bin/php-cs-fixer fix src`.

## Frontend i CSS

- Budowanie CSS:
  ```
  # Jednorazowo (prod)
  npx tailwindcss -c tailwind.config.js -i ./resources/css/app.css -o ./public/assets/app.css --minify

  # Tryb watch (dev)
  npx tailwindcss -c tailwind.config.js -i ./resources/css/app.css -o ./public/assets/app.css --watch
  ```
- PostCSS/Autoprefixer są używane automatycznie przez Tailwind (konfiguracja w postcss.config.js).
- Rekomendowane skrypty npm (do dodania w package.json):
  ```json
  {
    "scripts": {
      "dev": "tailwindcss -c tailwind.config.js -i ./resources/css/app.css -o ./public/assets/app.css --watch",
      "build": "tailwindcss -c tailwind.config.js -i ./resources/css/app.css -o ./public/assets/app.css --minify"
    }
  }
  ```
- Integracja: Dołącz `<link rel="stylesheet" href="/assets/app.css">` w widokach HTML.

## Struktura projektu

Zachowuj spójny, warstwowy układ. Na podstawie analizy repozytorium, struktura jest następująca (tree-like):

```
DoDomuDojade/
├── public/                     # Punkt wejścia i publiczne assets
│   ├── index.php               # Główny entry point (routing, bootstrap)
│   └── assets/
│           ├── resources/      # Źródła frontendu
│           └── styles/         # Style
│               └── style.css
├── src/                        # Kod źródłowy backendu
│   ├── core/                   # Bazowe klasy (np. bootstrap, modele bazowe)
│   ├── config/                 # Konfiguracje (np. routes.php jeśli oddzielone)
│   ├── models/                 # Modele danych (zapytania PDO do API/bazy)
│   ├── controllers/            # Kontrolery obsługujące żądania (np. AirlyController.php)
│   ├── services/               # Serwisy domenowe (logika biznesowa)
│   └── views/                  # Szablony HTML/PHP (jeśli server-side rendering)
├── logs/                       # Logi aplikacji (app.log)
├── tests/                      # Testy (Unit/, Integration/)    
├── vendor/                     # Zależności Composer (nie commituj)
├── .env                        # Zmienne środowiskowe (nie commituj)
├── .env.example                # Przykładowy .env
├── composer.json               # Zależności PHP
├── package.json                # Zależności npm
├── tailwind.config.js          # Konfig Tailwind
├── postcss.config.js           # Konfig PostCSS
└── phpunit.xml                 # Konfig testów
```

## Wersjonowanie i wydania

- Używaj Semantic Versioning (SemVer): MAJOR.MINOR.PATCH.
- Tagi Git: `git tag v1.0.0; git push --tags`.
- Wydania: Twórz releases na GitHub z notatkami i artifactami (np. zipped build).

## Kontrybucje

- Twórz feature branche od main/dev (np. `feature/new-api`).
- PR z opisem, checklistą i linkami do zadań.
- Code review: Minimum 1 approver.
- Commit messages: Konwencja Conventional Commits (feat:, fix:, chore:).

## Bezpieczeństwo

- Nie loguj danych poufnych (hasła, tokeny, PII).
- Waliduj wszystkie dane wejściowe (długości, formaty, typy).
- Przy bazie danych używaj wyłącznie zapytań parametryzowanych (`$stmt->execute([':param' => $value]);`).
- Konfiguruj nagłówki bezpieczeństwa na warstwie serwera WWW (np. `header('X-Frame-Options: DENY');` w index.php).
- Aktualizuj zależności (composer/npm) regularnie; monitoruj CVE via `composer audit`.

## Rozwiązywanie problemów

- **Błąd 500**: Sprawdź logi (`logs/app.log`); włącz `APP_DEBUG=true`.
- **Brak danych API**: Weryfikuj klucze w .env; testuj curl-em.
- **CSS nie ładuje**: Uruchom `npm run build`; sprawdź ścieżki w HTML.
- **Baza nie łączy**: Sprawdź PDO exceptions; testuj połączenie w teście.

## FAQ

- **Jak dodać nowe źródło danych?** Dodaj serwis w `src/services/`, endpoint w .env, routing w index.php.
- **Dlaczego bez frameworka?** Dla lekkości i nauki core PHP.
- **Czy mobilne?** Tak, Tailwind jest responsive; testuj na urządzeniach.
- **Licencja?** Sprawdź LICENSE file (domyślnie MIT jeśli brak).

## Autorzy

© SLOths4 2025
- Franciszek Kruszewski [@Kruszewski](https://github.com/Kruszewski)
- Igor Woźnica [@hexer7](https://github.com/hexer7)
- Tymoteusz Stobiński [@tymS258](https://github.com/tymS258)