# Dokumentacja ścieżki `/panel`

## Struktura endpointa

### Routing

Endpoint GET `/panel` jest zarejestrowany w `public/index.php` i mapuje się do `PanelController::index()`:

`GET /panel` → `PanelController::class`, `index`

**Middleware**: `AuthMiddleware::class` — wymaga uwierzytelnienia użytkownika.

## Architektura i przepływ żądania

```
HTTP GET /panel
↓
FastRoute Dispatcher (public/index.php)
↓
MiddlewarePipeline
├─ RequestContextMiddleware (buduje kontekst żądania)
├─ ExceptionMiddleware (obsługa wyjątków)
├─ AuthMiddleware (weryfikacja autentykacji) ← WYMAGANE
├─ LocaleMiddleware (ustawia lokalność)
└─ CsrfMiddleware (weryfikacja tokenu CSRF)
↓
PanelController::index()
↓
TwigRenderer::render(TemplateNames::PANEL)
↓
HTTP Response (HTML)
```

## Metody pomocnicze kontrolera

Kontroler zawiera kilka metod pomocniczych do formatowania danych:

### `buildUsernamesMap(array $users): array`

Tworzy mapę ID użytkowników do loginów:

```php
private function buildUsernamesMap(array $users): array
{
    $usernames = [];
    foreach ($users as $user) {
        $usernames[$user->id] = $user->username;
    }
    return $usernames;
}
```

**Użycie**: Przy renderowaniu stron z referencjami do użytkowników (countdowns, announcements)

### `formatCountdowns(array $countdowns): array`

Formatuje obiekty liczników do postaci gotowej dla widoku:

```php
private function formatCountdowns(array $countdowns): array
{
    $formatted = [];
    foreach ($countdowns as $countdown) {
        $formatted[] = (object)[
            'id' => $countdown->id,
            'title' => $countdown->title,
            'userId' => $countdown->userId,
            'countTo' => $countdown->countTo instanceof DateTimeImmutable
                ? $countdown->countTo->format('Y-m-d')
                : $countdown->countTo,
        ];
    }
    return $formatted;
}
```

**Formatowanie dat**: Konwertuje `DateTimeImmutable` do formatu `Y-m-d`

### `formatAnnouncements(array $announcements): array`

Formatuje obiekty ogłoszeń do postaci gotowej dla widoku:

- Konwertuje daty do formatu `Y-m-d H:i:s`
- Zmienia enum Status na string (`status->name`)
- Zapewnia konsystentne pola dla każdego ogłoszenia

## Pochodne metody (subpages)

Kontroler zawiera również metody dla podstron panelu:

### `users(): ResponseInterface`

Wyświetla stronę zarządzania użytkownikami.

**Logika**:
1. Pobiera wszystkich użytkowników via `GetAllUsersUseCase`
2. Loguje zdarzenie
3. Renderuje szablon `USERS`

### `countdowns(): ResponseInterface`

Wyświetla stronę zarządzania licznikami.

**Logika**:
1. Pobiera użytkowników i liczniki
2. Buduje mapę nazw użytkowników
3. Formatuje liczniki
4. Renderuje szablon `COUNTDOWNS`

### `announcements(): ResponseInterface`

Wyświetla stronę zarządzania ogłoszeniami.

**Logika**:
1. Pobiera użytkowników i ogłoszenia
2. Mapuje ogłoszenia na DTO za pomocą `AnnouncementViewMapper`
3. Segreguje na dwie kategorie:
    - `pendingAnnouncements` — status `PENDING`
    - `decidedAnnouncements` — pozostałe statusy
4. Renderuje szablon `ANNOUNCEMENTS`

### `modules(): ResponseInterface`

Wyświetla stronę zarządzania modułami.

**Logika**:
1. Pobiera wszystkie moduły
2. Mapuje na `ModuleViewDTO` z tłumaczeniami
3. Renderuje szablon `MODULES`

## Dependency Injection (Kontener)

Instancja `PanelController` jest konfigurowana w `bootstrap/bootstrap.php`:

```php
$container->set(PanelController::class, fn(Container $c) => new PanelController(
    $c->get(RequestContext::class),
    $c->get(TwigRenderer::class),
    $c->get(FlashMessengerService::class),
    $c->get(LoggerInterface::class),
    $c->get(GetAllModulesUseCase::class),
    $c->get(GetAllUsersUseCase::class),
    $c->get(GetAllCountdownsUseCase::class),
    $c->get(GetAllAnnouncementsUseCase::class),
    $c->get(Translator::class),
    $c->get(AnnouncementViewMapper::class),
));
```

**Kontener**: Niestandardowa implementacja DI (nie Laravel, nie Symfony) — minimalistyczna, efektywna dla małych aplikacji.

## Bezpieczeństwo

### Autentykacja

- Każdy endpoint pod `/panel` wymaga `AuthMiddleware`
- Middleware sprawdza obecność sessionu użytkownika
- Brak autentykacji → redirect na `/login`

### CSRF Protection

- Wszystkie żądania POST/PATCH/DELETE przechodzą przez `CsrfMiddleware`
- Wymaga poprawnego tokenu CSRF w żądaniu

## Szablony (Templates)

Szablony są przechowywane w `src/Presentation/View/templates/` i renderowane przez Twig:

- `PANEL` — strona główna panelu administracyjnego
- `USERS` — zarządzanie użytkownikami
- `COUNTDOWNS` — zarządzanie licznikami
- `ANNOUNCEMENTS` — zarządzanie ogłoszeniami
- `MODULES` — zarządzanie modułami

Mapowanie nazw szablonów w enum `TemplateNames` (w `src/Presentation/View/TemplateNames.php`)

## API Endpoints (powiązane)

Poniższe endpoint-y API pracują z danymi wyświetlanymi w panelu:

### Użytkownicy
- `POST /api/user` — dodaj użytkownika
- `DELETE /api/user/{id}` — usuń użytkownika

### Ogłoszenia
- `POST /api/announcement` — dodaj ogłoszenie
- `GET /api/announcement` — pobierz wszystkie
- `GET /api/announcement/{id}` — pobierz pojedyncze
- `PATCH /api/announcement/{id}` — edytuj ogłoszenie
- `DELETE /api/announcement/{id}` — usuń ogłoszenie
- `POST /api/announcement/{id}/approve` — zaakceptuj ogłoszenie
- `POST /api/announcement/{id}/reject` — odrzuć ogłoszenie

### Liczniki
- `POST /api/countdown` — dodaj licznik
- `PATCH /api/countdown/{id}` — edytuj licznik
- `DELETE /api/countdown/{id}` — usuń licznik

### Moduły
- `PATCH /api/module/{id}` — edytuj moduł
- `POST /api/module/{id}/toggle` — włącz/wyłącz moduł
