# API i routing

Wszystkie ścieżki są zdefiniowane w `public/index.php` (FastRoute). Ten dokument opisuje **rzeczywisty** kontrakt HTTP — to on musi zostać zachowany przy przepisywaniu backendu, bo frontend (Alpine.js w szablonach) z niego korzysta.

## Cykl życia żądania

1. `public/index.php` ładuje `bootstrap/bootstrap.php`:
    - `display_errors=0`, handler zamieniający warningi/notice'y na `ErrorException`,
    - handler shutdown zwracający JSON 500 przy błędach fatalnych,
    - Dotenv (`createImmutable`),
    - `Container` + providery w kolejności: `SharedProvider`, `UserProvider`, `AnnouncementProvider`, `DisplayProvider`.
2. `ServerRequest::fromGlobals()` (Guzzle PSR-7). Metody inne niż GET/POST/PUT/DELETE/PATCH/HEAD/OPTIONS → 400.
3. `StaticFileHandlingHelper::serve()` — pliki statyczne (css/js/png/jpg/jpeg/ico/svg) z `public/`, z ochroną przed path traversal, `Cache-Control: public, max-age=86400`.
4. Dispatcher FastRoute → 404 / 405 / znaleziony handler.
5. Kontroler jest pobierany z kontenera, budowany jest pipeline middleware i wywoływana akcja `$controller->$method($vars)`.

## Middleware

Kolejność (od zewnątrz do środka):

```
RequestContextMiddleware → ExceptionMiddleware → [AuthMiddleware] → LocaleMiddleware → CsrfMiddleware → kontroler
```

| Middleware | Działanie |
|---|---|
| `RequestContextMiddleware` | Ustawia `scope` = `admin` dla ścieżek `/panel/*`, inaczej `user`. |
| `ExceptionMiddleware` | Łapie wyjątki i zamienia je na odpowiedź (JSON lub redirect z flash). Szczegóły niżej. |
| `AuthMiddleware` | Tylko na trasach chronionych. Brak sesji → `302 /login` (także dla wywołań API!). W przeciwnym razie ładuje `User` do kontekstu. |
| `LocaleMiddleware` | Locale `en`/`pl` (domyślnie `en`): `?lang=` → `$_SESSION['locale']` → `Accept-Language` → `en`. |
| `CsrfMiddleware` | Generuje token; dla POST/PUT/PATCH/DELETE wymaga go w nagłówku `X-CSRF-Token` (lub `X-XSRF-TOKEN`, pole `_token`). Dotyczy też tras publicznych (`/api/authenticate`, `/logout`, `/api/announcement/propose`). |

## Tabela tras

Legenda: **A** = wymaga `AuthMiddleware`.

### Strony HTML

| Metoda | Ścieżka | Akcja | Auth | Szablon |
|---|---|---|---|---|
| GET | `/` | `HomeController::index` | – | `pages/index` |
| GET | `/propose` | `HomeController::proposeAnnouncement` | – | `pages/announcement-propose` |
| GET | `/display` | `DisplayController::index` | – | `pages/display` |
| GET | `/login` | `LoginController::show` | – | `pages/login` |
| POST | `/logout` | `LoginController::logout` | – | 302 → `/login` |
| GET | `/panel` | `PanelController::index` | A | `pages/panel` |
| GET | `/panel/users` | `PanelController::users` | A | `pages/user` |
| GET | `/panel/countdowns` | `PanelController::countdowns` | A | `pages/countdown` |
| GET | `/panel/announcements` | `PanelController::announcements` | A | `pages/announcement` |
| GET | `/panel/modules` | `PanelController::modules` | A | `pages/module` |

### API JSON (panel)

| Metoda | Ścieżka | Akcja | Auth | Odpowiedź |
|---|---|---|---|---|
| POST | `/api/authenticate` | `LoginController::authenticate` | – | 200 `{success: true, redirect: "/panel"}` |
| GET | `/api/user` | `UserController::getAll` | A | 200 `[{id, username}]` |
| POST | `/api/user` | `UserController::add` | A | 201 `{success, message}` |
| PATCH | `/api/user/{id}` | `UserController::update` | A | 200 `{success, message}` |
| DELETE | `/api/user/{id}` | `UserController::delete` | A | 204 |
| POST | `/api/user/{id}/change-password` | `UserController::changePassword` | A | 200 (tylko własne konto) |
| GET | `/api/announcement` | `AnnouncementController::getAll` | A | 200 `AnnouncementApiDTO[]` |
| POST | `/api/announcement` | `AnnouncementController::add` | A | 201 |
| GET | `/api/announcement/{id}` | `AnnouncementController::get` | A | 200 |
| PATCH | `/api/announcement/{id}` | `AnnouncementController::update` | A | 200 |
| DELETE | `/api/announcement/{id}` | `AnnouncementController::delete` | A | 204 |
| POST | `/api/announcement/{id}/approve` | `AnnouncementController::approve` | A | 200 |
| POST | `/api/announcement/{id}/reject` | `AnnouncementController::reject` | A | 200 |
| POST | `/api/announcement/propose` | `AnnouncementController::propose` | **publiczne** | 201 |
| GET | `/api/module` | `ModuleController::getAll` | A | 200 `[{id, moduleNameLabel, startTime "H:i", endTime "H:i", isActive}]` |
| PATCH | `/api/module/{id}` | `ModuleController::update` | A | 200 |
| POST | `/api/module/{id}/toggle` | `ModuleController::toggle` | A | 200 |
| GET | `/api/countdowns` *(l. mnoga)* | `CountdownController::getAll` | A | 200 `[{id, title, countTo "Y-m-d H:i", countToEdit "Y-m-d\TH:i", authorName}]` |
| POST | `/api/countdown` | `CountdownController::add` | A | 201 |
| PATCH | `/api/countdown/{id}` | `CountdownController::update` | A | 200 |
| DELETE | `/api/countdown/{id}` | `CountdownController::delete` | A | 204 |

Parametr `{id}` ma wzorzec `[a-z0-9_.]+`. Id ogłoszeń to stringi (`ann_…`), pozostałe są rzutowane `(int)` — `"abc"` staje się `0`.

### Ciała żądań

Zawsze surowy JSON (`json_decode(body, true)`):

| Endpoint | Pola |
|---|---|
| `POST /api/authenticate` | `username`, `password` |
| `POST /api/user` | `username`, `password` |
| `PATCH /api/user/{id}` | `username`, opcjonalnie `password` |
| `POST /api/user/{id}/change-password` | `password` |
| `POST /api/announcement` | `title`, `text`, `valid_until` |
| `PATCH /api/announcement/{id}` | `title`, `text`, `valid_until`, opcjonalnie `status` |
| `POST /api/announcement/propose` | `title`, `text`, opcjonalnie `expires_at` (domyślnie `ANNOUNCEMENT_DEFAULT_VALID_DATE`) |
| `POST/PATCH /api/countdown` | `title`, `count_to` |
| `PATCH /api/module/{id}` | `start_time`, `end_time` |

Hasła i nazwy użytkowników są **trimowane** w DTO.

### Display (publiczne)

Szczegóły w [Display](display.md).

| Metoda | Ścieżka | Moduł |
|---|---|---|
| GET | `/display/departure` | `tram` |
| GET | `/display/announcement` | `announcement` |
| GET | `/display/countdown` | `countdown` |
| GET | `/display/weather` | `weather` |
| GET | `/display/event` | `calendar` |
| GET | `/display/quote` | `quote` |
| GET | `/display/word` | `word` |

## Formaty odpowiedzi

### Koperta Display

Zawsze HTTP 200, nagłówek `X-Request-Id`:

```json
{"status": "active|inactive|empty", "data": <payload|null>, "message": <string|null>, "request_id": "<hex16>"}
```

### Koperta błędu (JSON)

Żądanie jest traktowane jako JSON, gdy `Accept` lub `Content-Type` zawiera `application/json` albo jest nagłówek `X-Requested-With: XMLHttpRequest`.

```json
{"status": "error", "data": {"code": "<ERROR_CODE>", "context": {}}, "message": "<przetłumaczony komunikat>", "request_id": "<id>"}
```

| Wyjątek | Status | Komunikat |
|---|---|---|
| `AuthenticationException`, `DomainException` (i pochodne) | `httpStatusCode` wyjątku (400/401/403/404/409) | klucz tłumaczenia przetłumaczony z `context` |
| `ApplicationException`, `InfrastructureException` | 500 | „An error occurred” |
| pozostałe | 500, `INTERNAL_SERVER_ERROR` | „An unexpected error occurred” |

Dla żądań nie-JSON błąd trafia do flash (`error`) i następuje `302` na `Referer` (lub `/`; dla błędów auth — `/login`).

## Sesja i bezpieczeństwo

- Natywna sesja PHP (`PHPSESSID`), bez jawnych flag `HttpOnly`/`Secure`/`SameSite` (obowiązują domyślne z `php.ini`).
- Klucze sesji: `user_id`, `user_ip_hash` (sha256 IP), `user_agent_hash` (sha256 UA), `csrf_token` (64 hex), `flash.success`, `flash.error`, `locale` (tylko odczyt).
- Logowanie: `session_regenerate_id(true)`, zapis `user_id` z odciskiem IP + User-Agent. Niezgodność odcisku → `session_destroy()`.
- Hasła: `password_hash(PASSWORD_DEFAULT)` → bcrypt `$2y$`.
- Brak ról — każdy zalogowany użytkownik może wszystko, poza zmianą cudzego hasła i usunięciem samego siebie.
- Brak limitu prób logowania.

## CLI (`bin/app`)

| Komenda | Argumenty | Działanie |
|---|---|---|
| `quote:fetch` | – | Pobiera cytat dnia z API i zapisuje (raz dziennie). |
| `word:fetch` | – | Pobiera słowo dnia z API i zapisuje (raz dziennie). |
| `announcement-rejected:delete` | `<YYYY-MM-DD>` | Usuwa odrzucone ogłoszenia z `decided_at` wcześniejszym niż data. |
| `user:add` | `<username> <password>` | Tworzy użytkownika. |

!!! warning
    Komendy łapią własne wyjątki, więc `bin/app` zwraca kod 0 także przy błędzie — cron nie wykryje awarii.
