# Plan migracji na Pythona

Cel: przepisać backend z PHP na Pythona **bez zmiany zachowania widocznego dla użytkownika** — ta sama baza danych, te same adresy URL, ten sam frontend (Alpine.js + Tailwind), te same kontrakty JSON. Dopiero po przełączeniu poprawiamy rzeczy, które zmieniają kontrakt.

## Zasady

1. **Kontrakt HTTP jest święty.** Ścieżki, metody, kody statusu, kształt JSON (w tym mieszanka `camelCase`/`snake_case`), nagłówek `X-CSRF-Token`, koperty `{status, data, message, request_id}` — patrz [API](api.md).
2. **Baza zostaje.** Python łączy się z istniejącą bazą PostgreSQL. Hashe haseł bcrypt `$2y$` działają w Pythonie bez migracji.
3. **Najpierw parytet, potem ulepszenia.** Błędy z [Stanu projektu](current-state.md) naprawiamy świadomie — każdy jako osobna decyzja, nie przy okazji.
4. **Prościej niż w PHP.** DDD w obecnej formie (interfejsy repozytoriów, ręczny kontener DI, walidatory w 3 warstwach) można spłaszczyć — FastAPI daje DI i walidację z pudełka.

## Proponowany stos

| Obszar | PHP (dziś) | Python (propozycja) | Uwagi |
|---|---|---|---|
| Framework HTTP | FastRoute + własny pipeline | **FastAPI** | Routing, DI (`Depends`), walidacja (Pydantic), OpenAPI za darmo. |
| Serwer | PHP-FPM | **uvicorn** (za nginx) | |
| Szablony | Twig | **Jinja2** | Składnia prawie identyczna. |
| Baza | PDO + `DatabaseService` | **SQLAlchemy 2.0** (Core lub ORM) + **psycopg 3** | |
| Migracje | ręczne pliki SQL | **Alembic** | Baseline = obecny schemat po migracjach. |
| Konfiguracja | phpdotenv + `Config` | **pydantic-settings** | Te same nazwy zmiennych `.env`. |
| HTTP klient | Symfony HttpClient | **httpx** | Z jawnymi timeoutami. |
| Google Calendar | google/apiclient | **google-api-python-client** + **google-auth** | Ten sam plik konta serwisowego. |
| Hasła | `password_hash` | **bcrypt** | `bcrypt.checkpw` obsługuje `$2y$`. |
| Sesje | `$_SESSION` | Starlette `SessionMiddleware` (podpisane ciasteczko) | Sesja zawiera tylko `user_id`, odciski, token CSRF, flash — mieści się w ciasteczku. |
| Logi | Monolog | `logging` + `TimedRotatingFileHandler` | |
| CLI | `bin/app` | **Typer** | Te same nazwy komend. |
| Testy | PHPUnit | **pytest** + `httpx.AsyncClient` | |
| Lint / typy | PHPStan lvl 7 | **ruff** + **mypy** (strict) | |
| Pakiety | Composer | **uv** + `pyproject.toml` | |

Wersja: Python 3.13.

## Proponowana struktura

```
app/
├── main.py                 # FastAPI app, middleware, montowanie routerów, static
├── config.py               # Settings (pydantic-settings) — odpowiednik Config
├── db.py                   # engine, sesja, tabele (SQLAlchemy)
├── security.py             # sesja, CSRF, auth dependency, bcrypt
├── i18n.py                 # tłumaczenia (resources/lang → JSON)
├── errors.py               # DomainError + handlery (koperta błędu)
├── domain/                 # encje i reguły (dataclasses), bez zależności od FastAPI/DB
│   ├── announcement.py
│   ├── countdown.py
│   ├── module.py
│   ├── user.py
│   └── events.py
├── services/               # use case'y (funkcje lub małe klasy)
├── repositories/           # zapytania SQL
├── integrations/           # tram.py, weather.py, calendar.py, quote.py, word.py
├── routers/
│   ├── pages.py            # /, /login, /panel/*, /display, /propose
│   ├── api.py              # /api/*
│   └── display.py          # /display/*
├── cli.py                  # Typer: quote:fetch, word:fetch, …
└── templates/              # szablony Jinja2 (przeniesione z Twig)
tests/
```

## Mapowanie PHP → Python

| PHP | Python |
|---|---|
| `public/index.php` (trasy) | `routers/*.py` |
| `Container` + `Providers/*` | `Depends()` w FastAPI — brak osobnego kontenera |
| `Config::fromEnv` | `config.Settings` |
| `ExceptionMiddleware` | `@app.exception_handler(DomainError)` + handler ogólny |
| `AuthMiddleware` | dependency `current_user` (redirect dla HTML, 401 dla JSON) |
| `CsrfMiddleware` | dependency `verify_csrf` na metodach zmieniających stan |
| `LocaleMiddleware` | dependency `locale` |
| `RequestContext`, `LocaleContext` (singletony) | `request.state` / dependency — **koniecznie per żądanie** |
| `*UseCase` | funkcje w `services/` |
| `*BusinessValidator`, `*ValidationHelper`, DTO | modele Pydantic + metody encji |
| `PDO*Repository` + `DatabaseService` | `repositories/*.py` (SQLAlchemy) |
| `SyncEventPublisher` + `events` | INSERT do `events` w tej samej transakcji |
| `TwigRenderer` + globals | `Jinja2Templates` + context processor (`user`, `flash`, `csrf`, `locale`, `config`) |
| `resources/lang/*.php` | `resources/lang/*.json` |
| `bin/app` | `cli.py` (Typer) |

## Na co uważać (różnice PHP → Python)

| Temat | Szczegół |
|---|---|
| Model procesów | PHP-FPM czyści stan po każdym żądaniu. W uvicornie stan globalny (użytkownik, locale, CSRF) wycieka między żądaniami — wszystko musi być per żądanie. |
| Hasła | DTO trimują hasło przed hashowaniem i weryfikacją — trzeba to zachować, inaczej część kont przestanie działać. |
| Sesje | Istniejących sesji PHP nie da się przenieść — po przełączeniu wszyscy logują się ponownie. |
| Strefa czasowa | PHP używa domyślnej strefy z `php.ini`, kolumny są bez strefy. W Pythonie ustawić jawnie `Europe/Warsaw` (`zoneinfo`) i porównywać `module.start_time` z `datetime.now(tz).time()`. |
| Formaty dat | `*_DATE_FORMAT` w `.env` używa składni PHP (`Y-m-d H:i:s`) — zastąpić stałymi formatami ISO zamiast tłumaczyć składnię. |
| Wyrażenia względne | `+1 year`, `+30 days` → `dateutil.relativedelta` albo liczba dni w nowej zmiennej. |
| Parsowanie dat z żądań | PHP `new DateTimeImmutable($s)` przyjmuje dowolny format strtotime; Pydantic przyjmie tylko ISO — sprawdzić, co wysyła frontend (`<input type="date">` / `datetime-local` → ISO, OK). |
| Błędy walidacji | FastAPI zwraca 422 w swoim formacie — trzeba nadpisać handler `RequestValidationError`, aby zwracał kopertę błędu z kodem 400. |
| Id z URL | PHP rzutuje `(int)"abc"` na `0`; w FastAPI `int` w ścieżce da 422 → mapować na 404/400. |
| Długość tekstu | `mb_strlen` = `len()` w Pythonie (code pointy). |
| Id ogłoszeń | Format `ann_<uniqid>`; nowe można generować jako `ann_` + `uuid4().hex` — walidacja sprawdza tylko prefiks. |
| ENUM w Postgresie | `announcement_status` → `sqlalchemy.Enum(..., name="announcement_status", create_type=False)`. |
| JSON | Klucze odpowiedzi 1:1 z PHP (`stopId`, `count_to`, `airlyColour`, `moduleNameLabel`, `countToEdit`). |
| Twig → Jinja2 | `|json_encode|raw` → `|tojson`; `{% import _self %}` → import z pliku; namespace `@components` → zwykła ścieżka; filtr `formatDate` do zarejestrowania; `strict_variables` → `StrictUndefined`. |
| Tailwind | Zmienić `source()` w `src/input.css` na nowy katalog szablonów. |

## Etapy

### Etap 0 — przygotowanie (w PHP)

- [ ] Uzgodnić decyzje z sekcji „Decyzje do podjęcia” (niżej).
- [ ] Skonsolidować schemat: `schema.sql` = stan po wszystkich migracjach (tabela `events`, `announcement.created_at DEFAULT now()`). To będzie baseline Alembica.
- [ ] Nagrać **testy kontraktowe**: dla każdej trasy z [API](api.md) zapisać przykładowe żądanie i odpowiedź z działającej aplikacji PHP (pliki JSON w `tests/contract/`). Te same fixtury będą testami parytetu dla Pythona.
- [ ] Zrzut produkcyjnej bazy (zanonimizowany) do testów lokalnych.

### Etap 1 — szkielet

- [ ] `pyproject.toml` (uv), ruff, mypy, pytest, CI w GitHub Actions (lint + testy).
- [ ] `config.py` z tymi samymi zmiennymi `.env`.
- [ ] `db.py`, Alembic z baseline'em (`alembic stamp head` na istniejącej bazie).
- [ ] `errors.py` — koperta błędu, tłumaczenia (`i18n.py`, konwersja `resources/lang/*.php` → JSON).
- [ ] Pliki statyczne z `public/assets`.

### Etap 2 — Display (tylko odczyt, publiczne)

- [ ] Widoczność modułów.
- [ ] Integracje: tram, weather (z cache w tabeli `weather`), calendar.
- [ ] Odczyty z bazy: announcement, countdown, quote, word.
- [ ] Szablon `display.html`.
- [ ] Testy kontraktowe `/display/*` zielone.

Po tym etapie można przełączyć **sam ekran** na Pythona (nginx kieruje `/display*` do uvicorna) — najwięcej ruchu, najmniej ryzyka.

### Etap 3 — logowanie i panel

- [ ] Sesja, CSRF, `current_user`, logowanie/wylogowanie (zgodność z hashami `$2y$`).
- [ ] Strony `/panel/*` + szablony Jinja2.
- [ ] API: user, announcement (z propozycjami), countdown, module.
- [ ] Zdarzenia domenowe do `events` w tej samej transakcji.

### Etap 4 — CLI i przełączenie

- [ ] Typer: `quote:fetch`, `word:fetch`, `announcement-rejected:delete`, `user:add` (z poprawnymi kodami wyjścia).
- [ ] Wdrożenie: systemd/Docker + nginx; cron z nowymi komendami.
- [ ] Przełączenie całego ruchu, okres obserwacji, usunięcie kodu PHP.

### Etap 5 — poprawki zmieniające zachowanie

Naprawy, które celowo zmieniają kontrakt lub dane (np. 401 zamiast 302 dla API, okna modułów przez północ, cache tramwajów i kalendarza, retencja tabeli `weather`, wydarzenia całodniowe).

## Decyzje do podjęcia

| # | Pytanie | Rekomendacja |
|---|---|---|
| D1 | Gdzie powstaje kod Pythona? | Ten sam repozytorium, katalog `app/` na gałęzi roboczej; PHP usuwany po przełączeniu. |
| D2 | Przełączenie stopniowe (nginx per ścieżka) czy jednorazowe? | Stopniowe: najpierw `/display*`, potem reszta. |
| D3 | ORM czy SQLAlchemy Core? | Core (zapytania są proste, mniej magii) albo ORM, jeśli zespół go zna. |
| D4 | Naprawiać błędy K1–K4 w PHP przed migracją? | K2–K4 tak (to schemat i dokumentacja, przydadzą się obu wersjom); K1 tylko jeśli PHP ma działać dłużej. |
| D5 | Zostawić konfigurowalne nazwy tabel i formaty dat w `.env`? | Nie — stałe w kodzie; mniej zmiennych, mniej błędów. |
| D6 | Przejść na `timestamptz`? | Tak, ale w etapie 5 (migracja Alembic). |
| D7 | Zachować dwujęzyczność (pl/en)? | Tak, zachowując klucze tłumaczeń (frontend ich nie używa, ale komunikaty błędów tak). |

## Szacunek zakresu

| Część | Rozmiar w PHP | Szacunek w Pythonie |
|---|---|---|
| Domena + use case'y | ~90 plików | ~10 modułów |
| HTTP (kontrolery, middleware) | ~25 plików | 3 routery + `security.py` + `errors.py` |
| Infrastruktura | ~50 plików | ~10 modułów (FastAPI/SQLAlchemy przejmują resztę) |
| Szablony | 20 plików Twig | 20 plików Jinja2 (konwersja mechaniczna) |
| Frontend JS/CSS | inline w szablonach | bez zmian |
