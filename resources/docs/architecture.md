# Architektura Domain-Driven Design

## Wstęp do DDD

**Domain-Driven Design (DDD)** to metodologia projektowania software'u, która kładzie nacisk na głębokie zrozumienie domeny biznesowej i odzwierciedlenie tej wiedzy w kodzie. Projekt DoDomuDojade implementuje DDD i clean architecture z wyraźnym podziałem na warstwy.


## Trochę o architekturze (Dla początkujących)
Projekt stara się utrzymać zgodność z architekturą DDD (Domain-Driven Design).

Punktem wejściowym całej aplikacji jest `index.php`. To tam znajdują się wszystkie ścieżki oraz ich obsługa.

Index zaczyna od inicjacji `bootstrap.php` w `bootstrap/bootstrap.php`. Tu z kolei dzieje się druga część magii. Wszystkie instancje klas są inicjowane, tak, żeby mogły potem zostać wykorzystane w DI (Dependency Injection).

Żeby wyjaśnić działanie aplikacji, przyjrzyjmy się przykładowej ścieżce `/login`.
1. Nasz serwer odpytuje `index.php` o tę ścieżkę
2. Router obecny w `index.php` odnajduje właściwą klasę i funkcję do uruchomienia. Jak to robi? Otóż w opisie ścieżki `$r->addRoute('GET', '/login', [LoginController::class, 'show']);` zawarta jest ta informacja.
3. Router uruchamia funkcję `show` w klasie `LoginController::class`
4. Funkcja `show` w akcji. Odziedziczona po `BaseController.php` funkcja `render` jest wykorzystywana do przekazania do użytkownika pliku z katalogu `src/Presentation/View/templates`
```
public function show(): ResponseInterface
{
    $this->logger->debug("Render login page request received");
    return $this->render(TemplateNames::LOGIN->value);
}
```
Ot cała magia ✨

Warto dodać, że niektóre ścieżki zawierają tzw. "middleware". Jest ono częścią wspólną między różnymi warstwami aplikacji. W naszej aplikacji na tę chwilę znajduje się middleware odpowiedzialne za:
- csrf (cross-site request forgery)
- translacje
- uwierzytelnianie

## 🎯 Główne Zasady DDD w Projekcie

### 1. Ubiquity of Language (Wszechobecność Języka)
Kod i dokumentacja używają jednolitego słownika biznesowego:
- **Announcement** — Ogłoszenie
- **Countdown** — Odliczanie
- **Quote** — Cytat
- **Word** — Słowo

## 🏗️ Warstwy Oprogramowania

### Warstwa Domain (src/Domain)
**Odpowiedzialność**: Zawiera czystą logikę biznesową niezależną od technologii

#### Entities
Entity reprezentuje obiekt z unikalną tożsamością (ID), który zmienia się w czasie.

```php
// Przykład: Announcement Entity
final class Announcement {
    public function __construct(
        private readonly ?AnnouncementId $id,
        public string $title,
        public string $text,
        private readonly DateTimeImmutable $createdAt,
        public DateTimeImmutable $validUntil,
        private readonly ?int $userId,
        public AnnouncementStatus $status = AnnouncementStatus::PENDING,
        public ?DateTimeImmutable $decidedAt = null,
        public ?int $decidedBy = null,
    ){}

    // Factory methods
    public static function create(...): self { }
    public static function proposeNew(...): self { }

    // Business methods
    public function approve(int $decidedBy): void { }
    public function reject(int $decidedBy): void { }
    public function update(...): void { }
}
```

**Cechy Entity:**
- Ma identyfikator (ID)
- Może być modyfikowana
- Zawiera zachowanie biznesowe (metody)
- Definiuje reguły biznesowe (invariants)

#### Value Objects
Value Objects reprezentują wartości, które nie zmieniają się i nie mają tożsamości.

```php
// Przykład: Password
final readonly class Password
{
    private string $hash;

    public function __construct(
        string $plainPassword,
        int $minLength = 8
    ) {
        if (mb_strlen($plainPassword) < $minLength) {
            throw ValidationException::invalidInput(['password' => ["Password too short (min $minLength)"]]);
        }
        $this->hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    }
    
    // Getter
    public function getHash(): string {}

    // Business logic
    public function verify(string $plainPassword): bool { }
}
```

**Cechy Value Object:**
- Niezmienność (immutable)
- Brak ID
- Porównanie po wartości, nie po referencji
- Samodzielna walidacja

#### Enums
Typy i statusy domeny.

```php
// Przykład: Announcement Status
enum AnnouncementStatus {
    case PENDING;
    case APPROVED;
    case REJECTED;
}
```

#### Exceptions
Wyjątki domenowe reprezentujące błędy biznesowe.

```php
// W src/Domain/Announcement/
final class AnnouncementException extends DomainException { }
```

### Warstwa Application (src/Application)
**Odpowiedzialność**: Orkiestracja logiki biznesowej, UseCase-y

```
src/Application/
├── Announcement/
│   ├── DTO/
│   │   ├── AddAnnouncementDTO.php
│   │   └── EditAnnouncementDTO.php
│   └── UseCase/
│       ├── CreateAnnouncementUseCase.php
│       ├── ApproveAnnouncementUseCase.php
│       └── RejectAnnouncementUseCase.php
├── Countdown/
│   ├── AddEditCountdownDTO.php
│   └── UseCase/
│       ├── CreateCountdownUseCase.php
│       └── UpdateCountdownUseCase.php
└── ...
```

#### Use Cases
Use Case opisuje pojedynczy, znaczący scenariusz użytkowania aplikacji.

```php
// Struktura Use Case
class CreateAnnouncementUseCase {
    public function __construct(
        private PDOAnnouncementRepository $repository,
        // inne zależności
    ) {}
    
    public function execute(AddAnnouncementDTO $dto, int $adminId): AnnouncementId {
        // 1. Validate request
        // 2. Create domain entity
        // 3. Call repository to save
        // 4. Trigger domain events (future)
    }
}
```

**Charakterystyka Use Case:**
- Jedna odpowiedzialność
- Orkiestracja między Domain a Infrastructure
- Brak logiki biznesowej (deleguje do Domain)
- Obsługuje zdarzenia i błędy

#### Data Transfer Objects (DTOs)
DTO-s transportują dane między warstwami bez logiki biznesowej.

```php
class AnnouncementDTO {
    public function __construct(
        public int    $id,
        public string $title,
        public string $text,
        public string $status,
        // ... inne pola
    ) {}
}
```

**Kiedy używać DTO-s:**
- Transfer danych z HTTP Request/Response
- Komunikacja między Use Cases
- Serializacja/deserializacja

### Warstwa Infrastructure (src/Infrastructure)
**Odpowiedzialność**: Implementacja technicznych szczegółów

```
src/Infrastructure/
├── Configuration/   # Konfiguracja i .env
├── Container.php    # DI Container
├── Database/        # PDO i obsługa bazy
├── ExternalApi/     # Integracje (Tram, Weather, Quote, Word, Calendar)
├── Helper/          # Funkcje pomocnicze
├── Logger/          # Konfiguracja logowania
├── Persistence/     # Implementacje repozytoriów (PDO*)
├── Security/        # Autoryzacja/uwierzytelnienie
├── Service/         # Serwisy aplikacyjne
├── Translation/     # Tłumaczenia
└── Twig/            # Renderowanie widoków
```

#### Repositories
Repository abstrahuje dostęp do danych (patrz: Repository Pattern).

```php
// Interface w Domain
interface AnnouncementRepositoryInterface {
    public function add(Announcement $announcement): AnnouncementId;
    public function update(Announcement $announcement): int;
    public function findById(AnnouncementId $id): ?Announcement;
    public function findAll(): array;
    public function delete(AnnouncementId $id): int;
}

// Implementacja w Infrastructure
class PDOAnnouncementRepository implements AnnouncementRepositoryInterface {
    public function add(Announcement $announcement): AnnouncementId {
        // SQL INSERT/UPDATE
    }
    
    public function findById(AnnouncementId $id): ?Announcement {
        // SQL SELECT
    }
}
```

**Rola Repository:**
- Abstrakcja dostępu do danych
- Brak SQL w Domain Layer
- Łatwa zamiana implementacji (np. mock w testach)
- Zgodność z DIP (Dependency Inversion Principle)

#### External Services
Serwisy integrujące się z zewnętrznymi API.

```php
class ExternalWordService {
    public function fetchDailyWord(): Word {
        // HTTP call do external API
        // Transformacja do Domain Entity
    }
}

class EmailService {
    public function send(string $email, string $message): void {
        // Sending email via SMTP/external service
    }
}
```

### Warstwa Presentation (src/Presentation/Http, src/Console)
**Odpowiedzialność**: Interfejsy użytkownika (HTTP, CLI)

```
src/Presentation/Http/
├── Controller/      # HTTP Controllers
│   ├── AnnouncementController.php
│   ├── UserController.php
│   └── ...
└── Response/        # Response helpers

src/Console/
└── Commands/        # CLI Commands
    ├── WordFetchCommand.php
    ├── QuoteFetchCommand.php
    ├── AnnouncementRejectedDeleteCommand.php
    └── AddUserCommand.php
```

**Charakterystyka Controllers:**
- Parsowanie HTTP Request
- Delegowanie do Use Case
- Formatowanie Response
- Obsługa HTTP specific logic (routing, auth, validation)

## 🔄 Przepływ Danych

### Typowy Scenariusz: Tworzenie Ogłoszenia

```
1. HTTP Request (POST /api/announcement)
        ↓
2. Controller (AnnouncementController)
   - Parsuje request
   - Tworzy DTO
        ↓
3. Use Case (CreateAnnouncementUseCase)
   - Tworzy Domain Entity
        ↓
4. Domain Entity (Announcement)
   - Aplikuje reguły biznesowe
        ↓
5. Repository Interface
        ↓
6. Repository Implementation (PDOAnnouncementRepository)
   - Wykonuje SQL INSERT
   - Zwraca entity z ID
        ↓
7. Use Case
   - Zwraca success
        ↓
8. Controller
   - Formatuje response
        ↓
9. HTTP Response (201 Created)
```

``` mermaid
graph TB
    A["HTTP Request<br/>(POST /api/announcement)"] -->|Parse | B["Controller"]
    B -->|Execute| C["Use Case"]
    C -->|Create| D["Domain Entity"]
    D -->|Follow Business Rules| E["Entity Created"]
    C -->|Save| F["Repository Interface"]
    F -->|Implement| G["PDO<br/>Implementation"]
    G -->|SQL INSERT| H["Database"]
    H -->|Return Entity| G
    G -->|Return to UseCase| F
    F -->|Return to UseCase| C
    C -->|Return to Controller| B
    B -->|Format Response| I["HTTP Response<br/>(201 Created)"]
```

## 🛡️ Invariants

Invariants to reguły biznesowe, które muszą być spełnione.

### Announcement Invariants
1. Announcement musi mieć unikalny tytuł (w kontekście)
2. Announcement musi mieć `validUntil` >= `createdAt`
3. Zatwierdzenie zmienia status z PENDING na APPROVED
4. Odrzucenie zmienia status z PENDING na REJECTED
5. Ogłoszenie jest ważne, tylko jeśli status = APPROVED i teraz < validUntil

``` php
public function isValid(): bool {
    return $this->status === AnnouncementStatus::APPROVED
        && new DateTimeImmutable() <= $this->validUntil;
}
```

## 📦 Dependency Injection

Projekt używa DI Container (`src/Infrastructure/Container.php`) zgodny z psr-11 `ContainerInterface`.

```php
// Container Registration
$container->set(
    PDOAnnouncementRepository::class,
    fn(Container $c) => new PDOAnnouncementRepository($pdo)
);

// Usage in Controller
$container->get(PDOAnnouncementRepository::class);
```

**Zasady:**
- Domain nie zależy od Infrastructure
- Application zależy od Domain interfaces
- Infrastructure implementuje Domain interfaces
- Presentation zależy od Application i Domain
