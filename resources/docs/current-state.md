# Stan projektu i znane problemy

Stan na 2026-10-27 (gałąź `main`). Decyzja twórcy: kod PHP nie będzie już poprawiany — poniższe problemy są listą wymagań dla [wersji w Pythonie](python-migration.md). Analiza obejmuje cały kod `src/`, szablony, schemat bazy i testy.

## Podsumowanie

| Obszar | Stan |
|---|---|
| Kod | ~200 plików PHP 8.5, warstwy Domain / Application / Infrastructure / Presentation, bez frameworka. |
| Zależności | FastRoute, Twig, Monolog, Guzzle PSR-7, Symfony HttpClient, Google API Client, phpdotenv. |
| Frontend | Szablony Twig + Alpine.js inline + Tailwind CSS 4. Brak osobnych plików JS. |
| Baza | PostgreSQL, 8 tabel, migracje ręczne. |
| Testy | 16 plików PHPUnit (~37 testów), głównie walidatory i infrastruktura. |
| CI | Tylko budowanie dokumentacji (`.github/workflows/docs.yml`). Testy i PHPStan nie są uruchamiane w CI. |

## Problemy krytyczne

| # | Problem | Miejsce | Skutek |
|---|---|---|---|
| K1 | `AnnouncementBusinessValidator` i `CountdownBusinessValidator` (konstruktory ze skalarami) nie są rejestrowane w żadnym providerze, a są pobierane z kontenera. | `src/Infrastructure/Container/Providers/AnnouncementProvider.php`, `SharedProvider.php:121-126` | Autowiring rzuca „Cannot autowire scalar parameter” — tworzenie/edycja/usuwanie ogłoszeń i odliczań kończy się 500. |
| K2 | `schema.sql` nie zawiera tabeli `events`. | `schema/schema.sql` | Na świeżej instalacji każda operacja publikująca zdarzenie kończy się 500. |
| K3 | `PDOAnnouncementRepository::add` nie wstawia `created_at`, które w `schema.sql` jest `NOT NULL` bez wartości domyślnej. | `src/Infrastructure/Persistence/PDOAnnouncementRepository.php:100` | INSERT ogłoszenia nie działa bez migracji `architecture_enhancements_001.sql`. |
| K4 | README każe wstawić moduł `announcements` (l. mnoga), enum oczekuje `announcement`. | `README.md`, `src/Domain/Module/ModuleName.php` | Strona modułów i `/display/announcement` zwracają błąd. |

## Błędy

| # | Problem | Miejsce |
|---|---|---|
| B1 | Cache Twig odwrócony: włączony tylko w trybie debug. | `src/Infrastructure/Twig/TwigFactory.php:39` |
| B2 | `UpdateModuleUseCase` waliduje `startTime` zamiast `endTime`. | `src/Application/Module/UseCase/UpdateModuleUseCase.php` |
| B3 | Klucz tłumaczeń `module_name` (pl) vs `module_names` (en) — w angielskiej wersji nazwy modułów wyświetlają się jako klucze. | `resources/lang/en.php:11`, `resources/lang/pl.php:3` |
| B4 | `RequestContextMiddleware` zapisuje `scope` w statycznym singletonie, a reszta aplikacji używa instancji z kontenera — `scope` nigdy nie jest widoczny. | `src/Presentation/Http/Middleware/RequestContextMiddleware.php:16` |
| B5 | `LOGGING_LEVEL=warning/error` jest ignorowane (rozróżniane tylko `debug`). | `src/Infrastructure/Logger/LoggerFactory.php` |
| B6 | Usunięty użytkownik z aktywną sesją → `TypeError` i 500 zamiast wylogowania. | `AuthMiddleware` |
| B7 | API zwraca `302 /login` zamiast 401 dla niezalogowanych wywołań JSON. | `AuthMiddleware` |
| B8 | Komendy CLI zawsze kończą się kodem 0. | `src/Console/Commands/*` |
| B9 | `?lang=` nie jest zapisywane w sesji. | `LocaleMiddleware` |
| B10 | Wydarzenia całodniowe z kalendarza pokazują bieżącą godzinę. | `src/Application/Display/GetDisplayEventsUseCase.php:39` |
| B11 | Airly parsowane po indeksie tablicy zamiast po nazwie wartości. | `src/Infrastructure/ExternalApi/Weather/WeatherService.php:56` |
| B12 | `Countdown::updateDetails` zmienia `userId`, ale repozytorium go nie zapisuje. `findCurrent` porównuje kolumnę `date` z datetime. | `PDOCountdownRepository` |
| B13 | `findValid` (`valid_until >= dziś`) i `Announcement::isValid()` (`now <= valid_until 00:00`) różnie traktują dzień wygaśnięcia. | `PDOAnnouncementRepository`, `Announcement` |
| B14 | `GetCountdownByIdUseCase` i `GetModuleByIdUseCase` dostają `*ValidationHelper` zamiast `*BusinessValidator` (nieużywane, ale zepsute). | `SharedProvider.php:124,129` |
| B15 | `word.word` jest UNIQUE — powtórzenie słowa w innym dniu powoduje błąd. | `schema/schema.sql` |
| B16 | Status ogłoszenia bindowany jako `PDO::PARAM_INT`. | `PDOAnnouncementRepository` |
| B17 | Brakujące klucze w JSON żądania → warning → `ErrorException` → 500 zamiast 400. | DTO w `src/Application/*/DTO` |
| B18 | `display.twig`: Alpine `3.x.x` nieprzypięty, podwójne pobranie odjazdów przy starcie, błąd logiczny `if (!this.error.push(...))`. | `src/Presentation/View/templates/pages/display.twig` |

## Edycja ogłoszeń w panelu

Zgłoszone przez twórcę („przy edycji ogłoszeń coś się psuje”) i prześledzone w kodzie:

| # | Problem | Miejsce | Objaw |
|---|---|---|---|
| E1 | Walidator nie powstaje w kontenerze (K1); kontroler jest budowany przed obsługą wyjątków. | `public/index.php:143`, `AnnouncementController.php:42` | Każda akcja na ogłoszeniach (lista, edycja, zatwierdzanie, usuwanie, propozycje) zwraca 500; toast „Błąd”. Strona panelu renderuje się normalnie. |
| E2 | Data „ważne do” = dziś jest odrzucana (porównanie północy z `now`); data walidowana nawet bez zmiany. | `AnnouncementBusinessValidator.php:68` | Nie da się ustawić dzisiejszej daty ani poprawić tekstu wygasłego ogłoszenia. |
| E3 | Pusta lub błędna data, brak pola `title` → wyjątek PHP. | `EditAnnouncementDTO.php:38-40` | 500 zamiast 400. |
| E4 | Zmiana statusu na PENDING ustawia `decided_at/by`. Status w edycji omija akcje zatwierdź/odrzuć. | `Announcement.php:203-207`, `announcement.twig:217` | Oczekujące ogłoszenie ma decydenta. |
| E5 | `decided_at` zapisywane w formacie `Y-m-d`. | `PDOAnnouncementRepository.php:138` | Traci godzinę decyzji. |
| E6 | UPDATE zatwierdzony przed zapisem zdarzenia; bez tabeli `events` → 500. | `EditAnnouncementUseCase.php:66-74` | Zmiana zapisana, a użytkownik widzi błąd i nieodświeżoną tabelę. |
| E7 | Limity w formularzu (50/500 znaków) inne niż w backendzie (5–255 / 10–65535). | `announcement.twig:200,206` | Licznik „620 / 500” dla długich propozycji; krótki tytuł odrzucany dopiero przez serwer. |
| E8 | Modal zamyka się także przy błędzie; wygasła sesja daje HTML zamiast JSON. | `announcement.twig:608` | Wpisane zmiany giną; komunikat „Unexpected token <”. |

## Bezpieczeństwo

- Ciasteczko sesji bez jawnych flag `HttpOnly`/`Secure`/`SameSite`.
- Brak limitu prób logowania.
- Odcisk sesji po IP psuje się za reverse proxy i w sieciach mobilnych.
- `DatabaseService` loguje wartości parametrów na poziomie debug — w tym hashe haseł.
- `CountdownRepositoryInterface::updateField` wstawia nazwę kolumny do SQL bez sanityzacji (martwy kod).

## Wydajność

- Brak timeoutów HTTP — zawieszone API blokuje worker PHP na ~60 s.
- Brak cache dla tramwajów i kalendarza; liczba zapytań rośnie liniowo z liczbą ekranów.
- Każdy INSERT/UPDATE wykonuje dodatkowe zapytanie do `information_schema`.
- N+1 zapytań o autorów ogłoszeń na ekranie.
- Tabela `weather` rośnie bez końca.

## Martwy kod

- `TramService::getStops/getLines/getRoutes/getMessageForBollard`
- `AnnouncementRepositoryInterface::findPending/findByTitle`, `UserRepositoryInterface::findByUsernamePartial`, `CountdownRepositoryInterface::updateField`
- `DatabaseService::bulkInsert/getColumn`
- `GetUserByUsernameUseCase`, `GetModuleByIdUseCase`, `GetCountdownByIdUseCase`
- Gałąź `/stream` w `public/index.php`
- Kolumna `user.is_active`
- Zależności `phpmailer/phpmailer` i npm `alpinejs`

## Pokrycie testami

| Pokryte | Niepokryte |
|---|---|
| Walidatory ogłoszeń, odliczań, modułów (przez helpery) | Wszystkie use case'y zapisujące dane |
| `AnnouncementId`, `Config`, `LanguageTranslator`, `SessionHelper`, `StaticFileHandlingHelper` | Przejścia stanów encji i zdarzenia |
| `SyncEventPublisher` (deduplikacja, ponawianie) | Repozytoria PDO i SQL |
| `TramService`, `GetDepartures`, `GetValidAnnouncements`, `GetAllUsers`, `FetchActiveQuote` | Cache pogody, widoczność modułów |
| Smoke test kontenera | Kontrolery, middleware (auth, CSRF), schemat |
