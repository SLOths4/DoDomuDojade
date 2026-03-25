# DoDomuDojadę — Dokumentacja architektury i API

Witaj w dokumentacji projektu **DoDomuDojadę**! Ta strona stanowi punkt wyjścia do zrozumienia struktury i architektury aplikacji.

## 📚 Struktura Dokumentacji

- **[Autogenerowana dokumentacja kodu](code/index.html)**
- **[Architektura](architecture.md)** — opisuje architekturę aplikacji
- **[Display](display.md)** — opisuje działanie najważniejszej funkcjonalności aplikacji
- **[Development](development.md)** — opisuje założenia dalszego rozwoju projektu

## Stos technologiczny
### Backend

- PHP 8.5
### Frontend

- Tailwind CSS
- Alpine.js
### Baza danych

- Postgres

## Przegląd projektu

DoDomuDojadę to aplikacja webowa pełniąca rolę wirtualnej tablicy informacyjnej. Składa się z:
- **Display** — publiczna strona z danymi (odjazdy, pogoda, cytat, słowo, wydarzenia). Renderowana w przeglądarce i zasilana przez API `GET /display/*`.
- **Panel administracyjny** — część chroniona (logowanie), w której zarządzasz użytkownikami, ogłoszeniami, modułami i odliczeniami.
- **Warstwa CLI** — komendy do zadań cyklicznych (pobieranie cytatu, słowa dnia, sprzątanie odrzuconych ogłoszeń).

## Struktura katalogów

Najważniejsze elementy repozytorium:
- `public/` — wejście aplikacji (`index.php`) i statyczne assety.
- `src/` — kod aplikacji (Application/Domain/Infrastructure/Presentation).
- `resources/docs/` — dokumentacja hostowana przez MkDocs.
- `resources/lang/` — tłumaczenia.
- `schema/` — schemat i migracje bazy danych.
- `bin/app` — CLI aplikacji.

## Moduły i integracje

W aplikacji działają moduły:
- **tram** — odjazdy tramwajów (ZTM/PEKA).
- **weather** — pogoda i jakość powietrza (IMGW + Airly).
- **quote** — cytat dnia.
- **word** — słowo dnia.
- **calendar** — wydarzenia z Google Calendar.
- **announcements** — ogłoszenia.
- **countdown** — odliczanie.

Widoczność modułów jest kontrolowana w tabeli `module` i sprawdzana po stronie API.

## Szybki start (lokalnie)

1. Zainstaluj zależności:
   ```shell
   composer install --dev
   npm ci
   ```
2. Skonfiguruj środowisko:
   ```shell
   cp .env.example .env
   ```
3. Uzupełnij wymagane zmienne środowiskowe (patrz sekcja niżej).
4. Utwórz bazę danych i wgraj schemat z `schema/schema.sql`.
5. Uruchom backend:
   ```shell
   php -S localhost:8080 -t public/ public/index.php
   ```
6. Uruchom front:
   ```shell
   npm run dev
   ```

## Konfiguracja `.env`

Poniżej lista kluczowych zmiennych środowiskowych. Wymagane zmienne muszą być ustawione, inaczej aplikacja przerwie start.

### Wymagane
- `LOGGING_DIRECTORY_PATH` — katalog na logi (np. `./logs`)
- `TWIG_CACHE_PATH` — katalog cache dla Twiga (np. `./var/cache/twig`)
- `AIRLY_API_KEY`, `AIRLY_ENDPOINT` — dane jakości powietrza
- `IMGW_WEATHER_URL` — endpoint IMGW
- `DB_USERNAME`, `DB_PASSWORD` — dane dostępu do bazy
- `TRAM_URL` — endpoint ZTM (Peka)
- `CALENDAR_API_KEY_PATH` — ścieżka do klucza Google Calendar
- `CALENDAR_ID` — ID kalendarza Google
- `QUOTE_API_URL`, `WORD_API_URL` — endpointy cytatu i słowa dnia

### Najczęściej używane opcjonalne
- `DB_HOST`, `DB_PORT`, `DB_NAME` (domyślnie host `localhost`, port `5432`, baza `dodomudojade`)
- `LOGGING_CHANNEL_NAME` (domyślnie `APP`)
- `LOGGING_LEVEL` (domyślnie `INFO`)
- `TWIG_DEBUG` (domyślnie `false`)
- `AIRLY_LOCATION_ID` (wymagane do pobierania danych z Airly)
- `STOP_ID` — lista przystanków, np. `AWF41,AWF05`
- `USER_TABLE_NAME` (domyślnie `user`) — nazwa tabeli użytkowników


### Szybki start (produkcja)

- Ustaw document root na `public/` (np. w Apache/Nginx).
- Konfiguruj PHP-FPM.
- Upewnij się, że katalog z logami (`LOGGING_DIRECTORY_PATH`) jest zapisywalny, ale nie publiczny.
- Zablokuj dostęp do katalogów źródłowych oraz .env.

#### Stwórz .env

```
cp .env.example .env
```

Ustaw `LOGGING_LEVEL=info` oraz odpowiednie ścieżki do logów i cache.

#### Ustaw cron jobs

##### Ustaw pobieranie dziennego słowa

```shell
bin/app word:fetch
```

##### Ustaw pobieranie dziennego cytatu

```shell
bin/app quote:fetch
```

##### Ustaw usuwanie odrzucanie ogłoszeń starszych niż podana data

```shell
bin/app announcement-rejected:delete {YYYY-MM-DD}
```

## Rozwiązywanie problemów
Rozwiązanie większości problemów staje się oczywiste po spojrzeniu do logów. Dlatego zacznij właśnie tam.

- **Błąd 500**: Sprawdź logi (`LOGGING_DIRECTORY_PATH/app.log`); sprawdź logi PHP-FPM.
- **Brak danych API**: Weryfikuj klucze w .env.
- **CSS się nie ładuje**: Uruchom `npm run build`; sprawdź, czy statyczne pliki są serwowane.
- **Baza się nie łączy**: Upewnij się, że podałeś/aś poprawny username i hasło.
- **Błąd bazy danych**: Sprawdź, czy baza danych ma prawidłową strukturę i zawiera wszystkie tabele;


## FAQ

- **Dlaczego projekt nie używa framework-a?** Głównym celem jest lekkość projektu oraz nauka PHP.

## Licencja

Ten projekt jest licencjonowany na warunkach **CC-BY-NC-4.0**

Oznacza to, że:

- ✅ możesz używać, modyfikować i dzielić się kodem
- ✅ musisz przypisać autorstwo
- ❌ nie możesz użytkować kodu komercyjnie bez zgody

## Autorzy

© **SLOths4** 2025

@AirScorpionK
@hexer7

---
**Masz pytania?** Otwórz [Issue](https://github.com/SLOths4/DoDomuDojade/issues) lub skontaktuj się z nami na [sloths4@spolecznaczworka.pl](mailto:sloths4@spolecznaczworka.pl).
