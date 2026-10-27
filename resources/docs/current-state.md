# Stan projektu i znane problemy

Stan na 2026-10-27 (gałąź `main` po scaleniu zmian z października 2026, m.in. commit `0396c40`). Decyzja twórcy: kod PHP nie będzie już poprawiany — otwarte problemy poniżej są listą wymagań dla [wersji w Pythonie](python-migration.md). Analiza obejmuje cały kod `src/`, szablony, schemat bazy i testy. Aplikacja nie była uruchamiana — wnioski pochodzą z lektury kodu.

## Podsumowanie

| Obszar | Stan |
|---|---|
| Kod | ~200 plików PHP 8.5, warstwy Domain / Application / Infrastructure / Presentation, bez frameworka. |
| Zależności | FastRoute, Twig, Monolog, Guzzle PSR-7, Symfony HttpClient, Google API Client, phpdotenv. |
| Frontend | Szablony Twig + Alpine.js inline + Tailwind CSS 4. Brak osobnych plików JS. |
| Baza | PostgreSQL, 8 tabel, migracje ręczne. |
| Testy | 16 plików PHPUnit (~37 testów), głównie walidatory i infrastruktura. |
| CI | Tylko budowanie dokumentacji (`.github/workflows/docs.yml`). Testy i PHPStan nie są uruchamiane w CI. |

## Naprawione na main (2026-10)

| # | Było | Jak naprawione |
|---|---|---|
| K1 | `AnnouncementBusinessValidator` i `CountdownBusinessValidator` nie były rejestrowane w kontenerze → 500 przy każdej operacji na ogłoszeniach i odliczaniach. | Rejestracja w `AnnouncementProvider.php:29` i `SharedProvider.php:119`. |
| K2 | `schema.sql` nie zawierał tabeli zdarzeń. | Tabela `event` (l. pojedyncza) w `schema.sql`; migracja `event_store_001.sql` dla istniejących baz. |
| K3 | INSERT ogłoszenia pomijał `created_at`. | `AnnouncementMapper::toDatabase` zapisuje `created_at` (pełny timestamp); kolumna `date` usunięta ze schematu. |
| E1 | Cała ścieżka `/api/announcement*` zwracała 500 (skutek K1). | Jak K1. |
| E5 | `decided_at` zapisywane bez godziny. | `AnnouncementMapper` zapisuje `Y-m-d H:i:s`. |
| B14 | `GetCountdownByIdUseCase`/`GetModuleByIdUseCase` dostawały zły typ walidatora. | Dostają `*BusinessValidator`. |
| – | Nazwy tabel konfigurowalne przez `*_TABLE_NAME`. | Stałe `TABLE_NAME` w repozytoriach; zmienne usunięte z `Config`. |

Częściowo naprawione:

- **B6** — usunięty użytkownik z aktywną sesją nie powoduje już `TypeError`: `PDOUserRepository::findById` rzuca `EntityNotFoundException` (404), które obsługuje `ExceptionMiddleware`. Sesja nadal nie jest jednak niszczona (brak wylogowania).
- **B10** — wydarzenia całodniowe nie pokazują już bieżącej godziny (`CalendarService` używa `start.date`, gdy brak `dateTime`), ale wyświetlają się jako `00:00`–`00:00` zamiast „cały dzień”.
- **B16** — status jest bindowany jako `PARAM_STR` w `add`, `update`, `findPending`, `deleteRejectedOlderThan`; `findValid` nadal używa `PARAM_INT` (`PDOAnnouncementRepository.php:67`).

## Problemy krytyczne (otwarte)

| # | Problem | Miejsce | Skutek |
|---|---|---|---|
| K4 | README na `main` każe wstawić moduł `announcements` (l. mnoga), enum oczekuje `announcement`. Poprawione w README na gałęzi z dokumentacją. | `README.md`, `src/Domain/Module/ModuleName.php` | Strona modułów i `/display/announcement` zwracają błąd. |
| K5 | Brak migracji dla istniejących baz: kolumna `announcement.date` (`NOT NULL`, bez domyślnej) nie jest usuwana żadną migracją, a kod już jej nie wstawia. | `schema/migrations/*`, `AnnouncementMapper.php` | Na bazie utworzonej ze starego `schema.sql` każdy INSERT ogłoszenia kończy się błędem, dopóki ktoś ręcznie nie usunie kolumny. |

## Błędy (otwarte)

| # | Problem | Miejsce |
|---|---|---|
| B1 | Cache Twig odwrócony: włączony tylko w trybie debug. | `src/Infrastructure/Twig/TwigFactory.php:39` |
| B2 | `UpdateModuleUseCase` waliduje `startTime` zamiast `endTime`. | `src/Application/Module/UseCase/UpdateModuleUseCase.php:73` |
| B3 | Klucz tłumaczeń `module_name` (pl) vs `module_names` (en) — w angielskiej wersji nazwy modułów wyświetlają się jako klucze. | `resources/lang/en.php:11`, `resources/lang/pl.php:3` |
| B4 | `RequestContextMiddleware` zapisuje `scope` w statycznym singletonie, a reszta aplikacji używa instancji z kontenera — `scope` nigdy nie jest widoczny. | `src/Presentation/Http/Middleware/RequestContextMiddleware.php:16` |
| B5 | `LOGGING_LEVEL=warning/error` jest ignorowane (rozróżniane tylko `debug`). | `src/Infrastructure/Logger/LoggerFactory.php:40` |
| B6 | Usunięty użytkownik z aktywną sesją dostaje błąd 404 zamiast wylogowania (patrz wyżej). | `AuthMiddleware`, `AuthenticationService::getCurrentUser` |
| B7 | API zwraca `302 /login` zamiast 401 dla niezalogowanych wywołań JSON. | `AuthMiddleware` |
| B8 | Komendy CLI łapią własne wyjątki — `bin/app` kończy się kodem 0 także przy błędzie. | `src/Console/Commands/*` |
| B9 | `?lang=` nie jest zapisywane w sesji. | `LocaleMiddleware` |
| B10 | Wydarzenia całodniowe wyświetlane jako `00:00`–`00:00`. | `src/Infrastructure/ExternalApi/Calendar/CalendarService.php:87` |
| B11 | Airly parsowane po indeksie tablicy zamiast po nazwie wartości. | `src/Infrastructure/ExternalApi/Weather/WeatherService.php:57` |
| B12 | `Countdown::updateDetails` zmienia `userId`, ale repozytorium go nie zapisuje. `findCurrent` porównuje kolumnę `date` z datetime — dzisiejsze odliczanie znika o północy. | `PDOCountdownRepository` |
| B13 | `findValid` (`valid_until >= dziś`) i `Announcement::isValid()` (`now <= valid_until 00:00`) różnie traktują dzień wygaśnięcia. | `PDOAnnouncementRepository`, `Announcement` |
| B15 | `word.word` jest UNIQUE — powtórzenie słowa w innym dniu powoduje błąd. | `schema/schema.sql` |
| B16 | `findValid` binduje status jako `PDO::PARAM_INT`. | `PDOAnnouncementRepository.php:67` |
| B17 | Brakujące klucze w JSON żądania → warning → `ErrorException` → 500 zamiast 400 (np. `$array['title']` bez `??`). | DTO w `src/Application/*/DTO` |
| B18 | `display.twig`: Alpine `3.x.x` nieprzypięty, podwójne pobranie odjazdów przy starcie, błąd logiczny `if (!this.error.push(...))`. | `src/Presentation/View/templates/pages/display.twig` |
| B19 | Użytkownik z wymuszoną zmianą hasła (`must_change_password`) ma dostęp do całego `/api/user*` (wyjątek po prefiksie), więc przed zmianą własnego hasła może dodawać i usuwać użytkowników oraz zmieniać im hasła. | `AuthMiddleware.php:51-55` |
| B20 | Stara migracja `architecture_enhancements_001.sql` dodaje `announcement.created_at` jako `date`, a nowy schemat i kod używają `timestamp` — bazy po tej migracji tracą godzinę utworzenia. | `schema/migrations/architecture_enhancements_001.sql:44` |

## Edycja ogłoszeń w panelu

Zgłoszone przez twórcę („przy edycji ogłoszeń coś się psuje”) i prześledzone w kodzie. E1 i E5 są naprawione na `main` (patrz wyżej); pozostałe nadal występują:

| # | Problem | Miejsce | Objaw |
|---|---|---|---|
| E2 | Data „ważne do” = dziś jest odrzucana (porównanie północy z `now`); data walidowana nawet bez zmiany. | `AnnouncementBusinessValidator.php` (`validateValidUntilDate`) | Nie da się ustawić dzisiejszej daty ani poprawić tekstu wygasłego ogłoszenia. |
| E3 | Pusta lub błędna data, brak pola `title` → wyjątek PHP. | `EditAnnouncementDTO.php` (`fromArray`) | 500 zamiast 400. |
| E4 | Zmiana statusu na PENDING ustawia `decided_at/by`. Status w edycji omija akcje zatwierdź/odrzuć. | `Announcement::update`, `announcement.twig:217` | Oczekujące ogłoszenie ma decydenta. |
| E6 | UPDATE zatwierdzony przed zapisem zdarzenia (brak transakcji). | `EditAnnouncementUseCase.php` | Gdy zapis zdarzenia się nie uda, zmiana jest zapisana, a użytkownik widzi błąd. |
| E7 | Limity w formularzu (50/500 znaków) inne niż w backendzie (5–255 / 10–65535). | `announcement.twig:200,206` | Licznik „620 / 500” dla długich propozycji; krótki tytuł odrzucany dopiero przez serwer. |
| E8 | Modal zamyka się także przy błędzie (`finally`); wygasła sesja daje HTML zamiast JSON. | `announcement.twig:524,557` | Wpisane zmiany giną; komunikat „Unexpected token <”. |

## Bezpieczeństwo

- Ciasteczko sesji bez jawnych flag `HttpOnly`/`Secure`/`SameSite`.
- Brak limitu prób logowania.
- Brak ról: każdy zalogowany użytkownik może zmienić hasło dowolnemu innemu (po zmianie cudzego hasła ustawiane jest `must_change_password`).
- Odcisk sesji po IP psuje się za reverse proxy i w sieciach mobilnych.
- `DatabaseService` loguje wartości parametrów na poziomie debug — w tym hashe haseł.
- `CountdownRepositoryInterface::updateField` wstawia nazwę kolumny do SQL bez sanityzacji (martwy kod).
- B19 (wyżej): wymuszona zmiana hasła nie blokuje API użytkowników.

## Wydajność

- Brak timeoutów HTTP — zawieszone API blokuje worker PHP na ~60 s.
- Brak cache dla tramwajów i kalendarza; liczba zapytań rośnie liniowo z liczbą ekranów.
- Każdy INSERT/UPDATE wykonuje dodatkowe zapytanie do `information_schema`.
- N+1 zapytań o autorów ogłoszeń na ekranie.
- Tabela `weather` rośnie bez końca.

## Martwy kod

- `TramService::getStops/getLines/getRoutes/findMessagesForBollard`
- `AnnouncementRepositoryInterface::findPending/findByTitle`, `UserRepositoryInterface::findByUsernamePartial`, `CountdownRepositoryInterface::updateField`
- `DatabaseService::bulkInsert/getColumn`
- `GetUserByUsernameUseCase`, `GetModuleByIdUseCase`, `GetCountdownByIdUseCase` (zarejestrowane, nieużywane)
- Gałąź `/stream` i `is_callable` w `public/index.php`
- Kolumna `user.is_active`
- Zależności `phpmailer/phpmailer` i npm `alpinejs`

## Pokrycie testami

| Pokryte | Niepokryte |
|---|---|
| Walidatory ogłoszeń, odliczań, modułów (przez helpery) | Wszystkie use case'y zapisujące dane |
| `AnnouncementId`, `Config`, `LanguageTranslator`, `SessionHelper`, `StaticFileHandlingHelper` | Przejścia stanów encji i zdarzenia |
| `SyncEventPublisher` (deduplikacja, ponawianie) | Repozytoria PDO, `AnnouncementMapper` i SQL |
| `TramService`, `GetDepartures`, `GetValidAnnouncements`, `GetAllUsers`, `FetchActiveQuote` | Cache pogody, widoczność modułów, kalendarz |
| Smoke test kontenera | Kontrolery, middleware (auth, CSRF, wymuszona zmiana hasła), schemat i migracje |
