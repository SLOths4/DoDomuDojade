# DoDomuDojade

![GitHub last commit](https://img.shields.io/github/last-commit/SLOths4/DoDomuDojade)
![GitHub issues](https://img.shields.io/github/issues/SLOths4/DoDomuDojade)
![GitHub stars](https://img.shields.io/github/stars/SLOths4/DoDomuDojade?style=social)

## O projekcie
DoDomuDojadę to aplikacja webowa, której głównym zadaniem jest wyświetlanie odjazdów komunikacji miejskiej. Aplikacja posiada równie kilka innych funkcjonalności, które pozwalają jej pełnić funkcję tablicy informacyjnej.

## Źródła danych
Aplikacja jest przystosowana do korzystania z ponizszych źródeł danych:
- [IMGW](https://www.imgw.pl/)
- [Airly](https://airly.org/)
- [Google Calendar](https://calendar.google.com/)

## Spis treści
- Wymagania
- Stos technologiczny
- Szybki start
- Konfiguracja środowiska
- Uruchamianie aplikacji
- Testy (Do dodania)
- Logowanie i diagnostyka
- Styl kodu i jakość
- Frontend i CSS
- Struktura projektu (Do dodania)
- CI/CD (Do dodania)
- Wersjonowanie i wydania
- Kontrybucje
- Bezpieczeństwo
- Rozwiązywanie problemów
- FAQ

---

## Wymagania
- PHP 8.4 (zalecane), rozszerzenia: PDO, mbstring, intl (opcjonalnie), json
- Composer 2.x
- Node.js 18+ oraz npm 9+ (do buildów frontendu)
- Dostęp do bazy danych (np. MySQL/MariaDB lub zgodna z PDO)
- Uprawnienia do zapisu dla katalogów cache/logs 

## Stos technologiczny
- Backend:
    - PHP 8.4
    - Routing: nikic/fast-route v1.3.0
    - Logowanie: monolog/monolog v3.9.0, psr/log v3.0.2
    - HTTP klient: symfony/http-client v7.2.4 (+ contracts)
    - Wczytywanie konfiguracji: vlucas/phpdotenv v5.6.2
    - Opcjonalne narzędzia: graham-campbell/result-type, phpoption/phpoption, psr/container, symfony/*-contracts, polyfills
    - Testy: PHPUnit
- Frontend:
    - Tailwind CSS 3.4.17
    - PostCSS 8.5.3, Autoprefixer 10.4.20

## Szybki start
1) Sklonuj repozytorium i zainstaluj zależności:
```shell script
# Bash
composer install
npm ci
```

2) Skonfiguruj zmienne środowiskowe:
```shell script
# Bash
cp .env.example .env
# Edytuj .env i ustaw brakujące wartości
```

3) Zbuduj frontend (jeśli dotyczy):
```shell script
# Bash
npx tailwindcss -c tailwind.config.js -i ./resources/css/app.css -o ./public/assets/app.css --minify
```

4) Uruchom backend lokalnie (przykłady):
```shell script
# PHP built-in server (w razie katalogu public/)
php -S localhost:8000 -t public
```

## Konfiguracja środowiska
Plik .env (ładowany przez vlucas/phpdotenv) powinien zawierać kluczowe zmienne. Przykładowy zestaw:

```dotenv
# Airly data
AIRLY_API_KEY=
AIRLY_LOCATION_ID=00000
AIRLY_ENDPOINT=https://airapi.airly.eu/v2/measurements/location?locationId=

# Calendar
CALENDAR_URL=https://calendar.google.com/calendar/ical/c_e22b26a985cffb8ff2a9afd9e3516d5ca1e5d608c2d3bf20807da38a40f71431%40group.calendar.google.com/public/basic.ics

# IMGW
IMGW_WEATHER_URL=https://danepubliczne.imgw.pl/api/data/synop/id/12330

# DATABASE
DB_HOST=
DB_USERNAME=""
DB_PASSWORD=""

# ZTM
ZTM_URL=https://www.peka.poznan.pl/vm/method.vm
```

## Uruchamianie aplikacji
- Lokalnie: php -S
- Produkcyjnie:
    - Ustaw docroot (np. public/) i zablokuj dostęp do katalogów źródłowych oraz .env.
    - Włącz buforowanie opcache, ustaw APP_DEBUG=false i odpowiedni poziom logów (np. warning).
    - Konfiguruj PHP-FPM i workerów zgodnie z obciążeniem.
    - Upewnij się, że katalog logs/ ma prawa do zapisu.

## Logowanie i diagnostyka
- Monolog zapisuje logi zgodnie z LOG_PATH i LOG_LEVEL.
- Poziomy: debug (lokalnie), info, warning, error (prod).

## Styl kodu i jakość
- Standard: PSR-12, typowanie w PHP 8.4, unikanie nadmiernych wyjątków w kontrolach przepływu.

## Frontend i CSS
- Budowanie CSS:
```shell script
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


## Struktura projektu (rekomendowana)
Zachowuj spójny, warstwowy układ:
- public/ – punkt wejścia aplikacji (index.php), zasoby publiczne (assets)
- src/
    - core/ – warstwa bazowa (np. Model, kontrolery, bootstrap)
    - models/ – logika dostępu do danych (PDO/Query)
    - controllers/ – logika sterująca, walidacje wejścia, mapowanie żądań
    - services/ – logika domenowa/serwisy
    - views/ – szablony (jeśli renderowane po stronie serwera)
- config/ – pliki konfiguracyjne i bootstrap środowiska
- logs/ – logi aplikacji
- tests/ – testy jednostkowe i integracyjne
- resources/ – źródła frontendu (css/js)
- vendor/ – zależności composera
- package.json, composer.json, tailwind.config.js, postcss.config.js, phpunit.xml

Uwaga: dopasuj nazwy folderów do rzeczywistego układu w repozytorium.

## Kontrybucje
- Twórz feature branche od main/dev.
- PR z opisem, checklistą i linkami do zadań.

## Bezpieczeństwo
- Nie loguj danych wrażliwych (hasła, tokeny, PII).
- Waliduj wszystkie dane wejściowe (długości, formaty, typy).
- Przy bazie danych używaj wyłącznie zapytań parametryzowanych.
- Konfiguruj nagłówki bezpieczeństwa na warstwie serwera WWW.
- Aktualizuj zależności (composer/npm) regularnie; monitoruj CVE.

---

## Autorzy
© SLOths4 2025
- Franciszek Kruszewski [@Kruszewski](https://github.com/Kruszewski)
- Igor Woźnica [@hexer7](https://github.com/hexer7)
- Tymoteusz Stobiński [@tymS258](https://github.com/tymS258)
