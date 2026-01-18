# Infrastructure Layer - API Reference

Warstwa Infrastructure zawiera implementację technicznych szczegółów, integracje z zewnętrznymi serwisami i zarządzaniem danymi.

## 📍 Struktura Infrastructure Layer

```
src/Infrastructure/
├── Container.php                # Dependency Injection Container
├── Repository/                  # Data Access Layer
│   ├── DatabaseAnnouncementRepository.php
│   ├── DatabaseUserRepository.php
│   ├── DatabaseWordRepository.php
│   ├── DatabaseQuoteRepository.php
│   └── DatabaseCountdownRepository.php
├── Service/                     # External Services
│   ├── ExternalWordApiService.php
│   ├── ExternalQuoteApiService.php
│   ├── EmailService.php
│   └── LoggerService.php
├── Factory/                     # Object Creation
│   ├── AnnouncementFactory.php
│   ├── UserFactory.php
│   └── ...
├── Security/                    # Security & Auth
│   ├── PasswordHasher.php
│   ├── TokenGenerator.php
│   └── AuthenticationService.php
├── Helper/                      # Utility Functions
│   ├── DateHelper.php
│   ├── StringHelper.php
│   └── ValidationHelper.php
├── Translation/                 # Internationalization
│   └── TranslationService.php
├── Trait/                       # Shared Traits
│   └── TimestampableTrait.php
└── View/                        # View Helpers
    └── TemplateRenderer.php
```

**Namespace**: `App\Infrastructure\`

---

## 🔌 Repositories

Repositories implementują abstrakcję dostępu do danych (patrz: Repository Pattern).

### DatabaseAnnouncementRepository

**Lokalizacja**: `src/Infrastructure/Repository/DatabaseAnnouncementRepository.php`

**Odpowiedzialność**: Persystencja danych Announcement w bazie danych

**Implementuje**: `AnnouncementRepositoryInterface` (Future)

**Zależności**:
```php
public function __construct(
    private PDO $pdo
)
```

#### save(Announcement $announcement): void

Zapisz (lub zaktualizuj) ogłoszenie w bazie.

**Parametry**:
- `Announcement $announcement` - Agregat do zapisania

**Efekty**:
- INSERT jeśli `$announcement->id === null`
- UPDATE jeśli `$announcement->id !== null`

**SQL**:
```sql
-- INSERT
INSERT INTO announcements (
    title, text, created_at, valid_until, user_id, status, decided_at, decided_by
) VALUES (...)

-- UPDATE
UPDATE announcements SET 
    title = ?, text = ?, status = ?, decided_at = ?, decided_by = ?
WHERE id = ?
```

**Wyjątki**:
- `DatabaseException` - Błąd SQL
- `ConstraintViolationException` - Naruszenie constraint'a

**Użycie**:
```php
$announcement = Announcement::createNew(...);
$repository->save($announcement);
```

---

#### findById(int $id): ?Announcement

Pobierz ogłoszenie po ID.

**Parametry**:
- `int $id` - ID ogłoszenia

**Zwraca**: 
- `Announcement` - Znalezione ogłoszenie
- `null` - Jeśli nie znalezione

**SQL**:
```sql
SELECT * FROM announcements WHERE id = ? LIMIT 1
```

---

#### findAll(): array

Pobierz wszystkie ogłoszenia.

**Zwraca**: `array<Announcement>`

---

#### findByStatus(string $status): array

Pobierz ogłoszenia o określonym statusie.

**Parametry**:
- `string $status` - Status (pending/approved/rejected)

**Zwraca**: `array<Announcement>`

---

#### findValid(): array

Pobierz tylko ważne ogłoszenia (approved i jeszcze nieugasłe).

**Zwraca**: `array<Announcement>` - Aktualnie widoczne

**SQL**:
```sql
SELECT * FROM announcements 
WHERE status = 'approved' 
AND valid_until >= NOW()
ORDER BY created_at DESC
```

---

#### delete(int $id): void

Usuń ogłoszenie.

**Parametry**:
- `int $id` - ID do usunięcia

---

### DatabaseUserRepository

**Lokalizacja**: `src/Infrastructure/Repository/DatabaseUserRepository.php`

Zarządza persystencją użytkowników.

#### save(User $user): void
Zapisz użytkownika.

#### findById(int $id): ?User
Pobierz po ID.

#### findByEmail(string $email): ?User
Pobierz po email.

#### existsEmail(string $email): bool
Sprawdź czy email istnieje.

---

### DatabaseWordRepository

Zarządza słowami dnia.

### DatabaseQuoteRepository

Zarządza cytatami.

---

## 🔗 External Services

Serwisy integrujące się z zewnętrznymi API.

### ExternalWordApiService

**Lokalizacja**: `src/Infrastructure/Service/ExternalWordApiService.php`

**Odpowiedzialność**: Pobieranie słów z zewnętrznego API

**Zależności**:
```php
public function __construct(
    private HttpClient $httpClient,
    private ConfigService $config
)
```

#### fetchDailyWord(): Word

Pobierz słowo dnia z zewnętrznego API.

**Zwraca**: `Word` - Transformowane do Domain Entity

**Flow**:
1. HTTP GET do external API
2. Parse response
3. Walidacja
4. Transformacja do Word Entity
5. Return

**Wyjątki**:
- `ExternalServiceException` - Problem z API
- `HttpException` - Błąd HTTP
- `InvalidResponseException` - Błędna struktura danych

**HTTP**:
```
GET https://api.external.com/word/daily
Accept: application/json
Authorization: Bearer {token}
```

**Response**:
```json
{
  "word": "ephemeral",
  "definition": "Lasting for a very short time",
  "example": "The beauty of cherry blossoms is ephemeral",
  "language": "en"
}
```

**Użycie**:
```php
// W CLI Command lub UseCase
$word = $this->wordService->fetchDailyWord();
$this->wordRepository->save($word);
```

---

### ExternalQuoteApiService

**Lokalizacja**: `src/Infrastructure/Service/ExternalQuoteApiService.php`

Pobieranie cytatów z zewnętrznego API.

#### fetchDailyQuote(): Quote

---

### EmailService

**Lokalizacja**: `src/Infrastructure/Service/EmailService.php`

**Odpowiedzialność**: Wysyłanie emaili

**Zależności**:
```php
public function __construct(
    private SmtpClient $smtpClient,
    private ConfigService $config
)
```

#### send(string $to, string $subject, string $body): void

Wyślij email.

**Parametry**:
- `string $to` - Adres email odbiorcy
- `string $subject` - Temat
- `string $body` - Treść (HTML/Plain)

**Efekty**: Email wysłany via SMTP

**Wyjątki**:
- `EmailException` - Błąd wysyłki

---

## 🏭 Factories

Fabryki tworzą kompleksowe obiekty z różnych źródeł.

### AnnouncementFactory

**Lokalizacja**: `src/Infrastructure/Factory/AnnouncementFactory.php`

#### createFromRequest(CreateAnnouncementRequest $request): Announcement

Utwórz Announcement Entity z Request.

```php
public static function createFromRequest(
    CreateAnnouncementRequest $request,
    ?int $userId = null
): Announcement {
    return Announcement::createNew(
        title: $request->title,
        text: $request->text,
        validUntil: new DateTimeImmutable($request->validUntil),
        userId: $userId ?? $request->userId,
    );
}
```

#### createFromDatabaseRow(array $row): Announcement

Utwórz Announcement Entity z rekordu bazy.

```php
public static function createFromDatabaseRow(array $row): Announcement {
    return new Announcement(
        id: (int) $row['id'],
        title: $row['title'],
        text: $row['text'],
        createdAt: new DateTimeImmutable($row['created_at']),
        validUntil: new DateTimeImmutable($row['valid_until']),
        userId: $row['user_id'] ? (int) $row['user_id'] : null,
        status: AnnouncementStatus::from($row['status']),
        decidedAt: $row['decided_at'] ? new DateTimeImmutable($row['decided_at']) : null,
        decidedBy: $row['decided_by'] ? (int) $row['decided_by'] : null,
    );
}
```

---

## 🔐 Security Components

Komponenty bezpieczeństwa i autentykacji.

### PasswordHasher

**Lokalizacja**: `src/Infrastructure/Security/PasswordHasher.php`

#### hash(string $password): string

Zahaszuj hasło.

```php
public function hash(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, [
        'cost' => 12
    ]);
}
```

#### verify(string $password, string $hash): bool

Sprawdź hasło.

```php
public function verify(string $password, string $hash): bool {
    return password_verify($password, $hash);
}
```

---

### TokenGenerator

**Lokalizacja**: `src/Infrastructure/Security/TokenGenerator.php`

#### generateJWT(User $user, int $expiresIn = 3600): string

Wygeneruj JWT token dla użytkownika.

```php
public function generateJWT(User $user, int $expiresIn = 3600): string {
    $payload = [
        'user_id' => $user->id,
        'email' => $user->email,
        'role' => $user->role,
        'iat' => time(),
        'exp' => time() + $expiresIn
    ];
    
    return JWT::encode($payload, $this->secret, 'HS256');
}
```

#### generateRefreshToken(User $user): string

Wygeneruj refresh token.

---

### AuthenticationService

**Lokalizacja**: `src/Infrastructure/Security/AuthenticationService.php`

#### authenticate(string $email, string $password): User

Uwierzytelnij użytkownika.

```php
public function authenticate(string $email, string $password): User {
    $user = $this->userRepository->findByEmail($email);
    
    if (!$user || !$this->passwordHasher->verify($password, $user->passwordHash)) {
        throw new InvalidCredentialsException();
    }
    
    return $user;
}
```

---

## 🛠️ Helpers

Funkcje pomocnicze.

### DateHelper

**Lokalizacja**: `src/Infrastructure/Helper/DateHelper.php`

```php
class DateHelper {
    public static function now(): DateTimeImmutable {
        return new DateTimeImmutable();
    }
    
    public static function tomorrow(): DateTimeImmutable {
        return self::now()->modify('+1 day');
    }
    
    public static function addDays(DateTimeImmutable $date, int $days): DateTimeImmutable {
        return $date->modify("+{$days} days");
    }
    
    public static function isExpired(DateTimeImmutable $date): bool {
        return $date < self::now();
    }
}
```

### StringHelper

```php
class StringHelper {
    public static function slugify(string $text): string {
        return strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($text)));
    }
    
    public static function truncate(string $text, int $length = 100): string {
        if (strlen($text) <= $length) return $text;
        return substr($text, 0, $length) . '...';
    }
}
```

### ValidationHelper

```php
class ValidationHelper {
    public static function isValidEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function isValidUrl(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
```

---

## 🌐 Translation Service

Obsługa wielojęzyczności.

### TranslationService

**Lokalizacja**: `src/Infrastructure/Translation/TranslationService.php`

```php
public function translate(string $key, string $locale = 'pl'): string {
    $messages = $this->loadMessages($locale);
    return $messages[$key] ?? $key;
}

public function t(string $key, array $params = [], string $locale = 'pl'): string {
    $message = $this->translate($key, $locale);
    foreach ($params as $key => $value) {
        $message = str_replace(":$key", $value, $message);
    }
    return $message;
}
```

**Użycie**:
```php
$this->translator->t('announcement.created', ['title' => $announcement->title]);
// "Ogłoszenie 'Nowa promocja' zostało utworzone"
```

---

## 🚚 Dependency Injection Container

### Container.php

**Lokalizacja**: `src/Infrastructure/Container.php`

Zarządzanie zależnościami i lifecycle'em obiektów.

```php
class Container {
    private array $services = [];
    private array $singletons = [];
    
    public function register(string $id, mixed $definition): void {
        $this->services[$id] = $definition;
    }
    
    public function singleton(string $id, callable $definition): void {
        $this->singletons[$id] = $definition;
    }
    
    public function get(string $id): mixed {
        if (isset($this->singletons[$id])) {
            // Zwróć ten sam instancję
            return $this->singletons[$id]($this);
        }
        
        if (isset($this->services[$id])) {
            return $this->services[$id]($this);
        }
        
        throw new ServiceNotFoundException($id);
    }
}
```

#### Rejestracja Serwisów

```php
// src/bootstrap/services.php
$container->singleton(PDO::class, function(Container $c) {
    return new PDO(
        'pgsql:host=localhost;dbname=dodomudojade',
        'ddd',
        $_ENV['DB_PASSWORD']
    );
});

$container->singleton(AnnouncementRepository::class, function(Container $c) {
    return new DatabaseAnnouncementRepository($c->get(PDO::class));
});

$container->singleton(CreateAnnouncementUseCase::class, function(Container $c) {
    return new CreateAnnouncementUseCase(
        $c->get(AnnouncementRepository::class),
    );
});
```

#### Użycie w Controller

```php
class AnnouncementController {
    public function __construct(
        private Container $container
    ) {}
    
    public function create(Request $request): Response {
        $useCase = $this->container->get(CreateAnnouncementUseCase::class);
        $dto = $useCase->execute($request);
        return Response::created($dto);
    }
}
```

---

## 📊 Architektura Warstwowa

```
Presentation (Http, Console)
        ↓
    (uses)
        ↓
Application (UseCase, DTO)
        ↓
    (uses)
        ↓
Domain (Entity, ValueObject, Enum)
   ↑    ↑
   |    |
(implements)
   |    |
Infrastructure (Repository, Service)
```

---

## 🎓 Best Practices

### ✅ DO

1. **Implementuj Repository interfaces (Future)**
   ```php
   interface AnnouncementRepository {
       public function save(Announcement $announcement): void;
       public function findById(int $id): ?Announcement;
   }
   ```

2. **Użyj Factories dla kompleksowych obiektów**
   ```php
   $entity = AnnouncementFactory::createFromDatabaseRow($row);
   ```

3. **Abstrakcjonuj external API calls**
   ```php
   interface WordApiService {
       public function fetchDailyWord(): Word;
   }
   ```

4. **Zarządzaj transakcjami**
   ```php
   $this->pdo->beginTransaction();
   try {
       $this->repository->save($entity);
       $this->pdo->commit();
   } catch (Exception $e) {
       $this->pdo->rollBack();
       throw $e;
   }
   ```

5. **Loguj ważne operacje**
   ```php
   $this->logger->info("Announcement approved", ['id' => $announcement->id]);
   ```

### ❌ DON'T

1. **Nie rób logiki biznesowej w Repository**
   ```php
   // ❌ WRONG
   public function save(Announcement $announcement): void {
       if ($announcement->title == '') throw new Exception();
   }
   ```

2. **Nie korzystaj z SQL w Service**
   ```php
   // ❌ WRONG - Use Repository
   $this->pdo->query("SELECT...");
   ```

3. **Nie importuj Application w Infrastructure**
   ```php
   // ❌ WRONG
   use App\Application\UseCase\CreateAnnouncementUseCase;
   ```

4. **Nie cache'uj obiekty bez namysłu**
   ```php
   // ⚠️ CAREFUL - Use singleton pattern
   private $announcement; // Will be stale!
   ```

---

## 📊 Mapowanie Warstw

| Warstwa | Komponenty | Odpowiedzialność |
|---------|-----------|-------------------|
| Domain | Entity, ValueObject | Logika biznesowa |
| Application | UseCase, DTO | Orkiestracja |
| Infrastructure | Repository, Service | Techniczne szczegóły |
| Presentation | Controller, Command | Interfejsy użytkownika |