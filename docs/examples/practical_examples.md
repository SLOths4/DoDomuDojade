# Praktyczne Przykłady i Wzorce

Rzeczywiste przykłady kodu pokazujące jak używać architekturę DDD w DoDomuDojadę.

## 📝 Przykład 1: Tworzenie Ogłoszenia

### Scenariusz: Nowy admin chce stworzyć ogłoszenie

```
HTTP POST /api/announcements
{
  "title": "Nowe godziny otwarcia",
  "text": "Od poniedziałku będziemy otwarci od 8:00 do 18:00",
  "valid_until": "2026-02-01T23:59:59Z"
}
```

### Krok 1: Controller (Presentation Layer)

```php
// src/Http/Controller/AnnouncementController.php
class AnnouncementController {
    public function __construct(
        private Container $container,
        private LoggerInterface $logger
    ) {}
    
    public function create(Request $request): Response {
        try {
            // Parse JSON body
            $data = $request->json();
            
            // Create Request object
            $createRequest = new CreateAnnouncementRequest(
                title: $data['title'] ?? '',
                text: $data['text'] ?? '',
                validUntil: $data['valid_until'] ?? '',
                userId: $request->userId() // from JWT token
            );
            
            // Validate request
            $errors = $createRequest->validate();
            if (!empty($errors)) {
                return Response::unprocessableEntity(['errors' => $errors]);
            }
            
            // Get UseCase from DI Container
            $useCase = $this->container->get(CreateAnnouncementUseCase::class);
            
            // Execute
            $announcementDTO = $useCase->execute($createRequest);
            
            return Response::created([
                'id' => $announcementDTO->id,
                'title' => $announcementDTO->title,
                'status' => $announcementDTO->status,
                'created_at' => $announcementDTO->createdAt
            ]);
            
        } catch (ValidationException $e) {
            return Response::unprocessableEntity(['errors' => $e->getErrors()]);
        } catch (AnnouncementException $e) {
            return Response::badRequest(['error' => $e->getMessage()]);
        } catch (Exception $e) {
            $this->logger->error("Failed to create announcement", ['error' => $e]);
            return Response::internalServerError();
        }
    }
}
```

### Krok 2: Use Case (Application Layer)

```php
// src/Application/UseCase/Announcement/CreateAnnouncementUseCase.php
class CreateAnnouncementUseCase {
    public function __construct(
        private AnnouncementRepository $announcementRepository,
        private LoggerInterface $logger
    ) {}
    
    public function execute(CreateAnnouncementRequest $request): AnnouncementDTO {
        // Step 1: Validate request
        $errors = $request->validate();
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
        
        // Step 2: Create Domain Entity using factory
        $announcement = AnnouncementFactory::createFromRequest(
            $request,
            userId: $request->userId
        );
        
        // Step 3: Save to repository
        $this->announcementRepository->save($announcement);
        
        // Step 4: Log
        $this->logger->info("Announcement created", [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'userId' => $request->userId
        ]);
        
        // Step 5: Transform to DTO
        return AnnouncementDTO::fromEntity($announcement);
    }
}
```

### Krok 3: Factory (Infrastructure Layer)

```php
// src/Infrastructure/Factory/AnnouncementFactory.php
class AnnouncementFactory {
    public static function createFromRequest(
        CreateAnnouncementRequest $request,
        int $userId = null
    ): Announcement {
        return Announcement::createNew(
            title: $request->title,
            text: $request->text,
            validUntil: new DateTimeImmutable($request->validUntil),
            userId: $userId ?? $request->userId,
        );
    }
}
```

### Krok 4: Domain Entity (Domain Layer)

```php
// src/Domain/Entity/Announcement.php
final class Announcement {
    public function __construct(
        public ?int               $id,
        public string             $title,
        public string             $text,
        public DateTimeImmutable  $createdAt,
        public DateTimeImmutable  $validUntil,
        public ?int               $userId,
        public AnnouncementStatus $status = AnnouncementStatus::PENDING,
        public ?DateTimeImmutable $decidedAt = null,
        public ?int               $decidedBy = null,
    ){}
    
    // Factory method - tworzy już zatwierdzone ogłoszenie
    public static function createNew(
        string            $title,
        string            $text,
        DateTimeImmutable $validUntil,
        int               $userId,
    ): self {
        return new self(
            id: null, // Still null, will be set by DB
            title: $title,
            text: $text,
            createdAt: new DateTimeImmutable(),
            validUntil: $validUntil,
            userId: $userId,
            status: AnnouncementStatus::APPROVED,
            decidedAt: new DateTimeImmutable(),
            decidedBy: $userId // Admin who created it
        );
    }
}
```

### Krok 5: Repository (Infrastructure Layer)

```php
// src/Infrastructure/Repository/DatabaseAnnouncementRepository.php
class DatabaseAnnouncementRepository {
    public function __construct(private PDO $pdo) {}
    
    public function save(Announcement $announcement): void {
        if ($announcement->id === null) {
            // INSERT
            $stmt = $this->pdo->prepare(
                'INSERT INTO announcements (
                    title, text, created_at, valid_until, user_id, status, decided_at, decided_by
                ) VALUES (
                    :title, :text, :created_at, :valid_until, :user_id, :status, :decided_at, :decided_by
                )'
            );
            
            $stmt->execute([
                ':title' => $announcement->title,
                ':text' => $announcement->text,
                ':created_at' => $announcement->createdAt->format('c'),
                ':valid_until' => $announcement->validUntil->format('c'),
                ':user_id' => $announcement->userId,
                ':status' => $announcement->status->name,
                ':decided_at' => $announcement->decidedAt?->format('c'),
                ':decided_by' => $announcement->decidedBy,
            ]);
            
            // Get inserted ID and update entity
            $announcement->id = (int) $this->pdo->lastInsertId();
        } else {
            // UPDATE
            $stmt = $this->pdo->prepare(
                'UPDATE announcements SET 
                    title = :title, 
                    text = :text, 
                    status = :status, 
                    decided_at = :decided_at, 
                    decided_by = :decided_by 
                WHERE id = :id'
            );
            
            $stmt->execute([
                ':id' => $announcement->id,
                ':title' => $announcement->title,
                ':text' => $announcement->text,
                ':status' => $announcement->status->name,
                ':decided_at' => $announcement->decidedAt?->format('c'),
                ':decided_by' => $announcement->decidedBy,
            ]);
        }
    }
}
```

### Krok 6: DTO (Application Layer)

```php
// src/Application/DataTransferObject/AnnouncementDTO.php
final class AnnouncementDTO {
    public function __construct(
        public int     $id,
        public string  $title,
        public string  $text,
        public string  $createdAt,
        public string  $validUntil,
        public ?int    $userId,
        public string  $status,
        public ?string $decidedAt,
        public ?int    $decidedBy,
    ) {}
    
    public static function fromEntity(Announcement $entity): self {
        return new self(
            id: $entity->id,
            title: $entity->title,
            text: $entity->text,
            createdAt: $entity->createdAt->format('c'),
            validUntil: $entity->validUntil->format('c'),
            userId: $entity->userId,
            status: $entity->status->name,
            decidedAt: $entity->decidedAt?->format('c'),
            decidedBy: $entity->decidedBy,
        );
    }
}
```

### HTTP Response

```json
HTTP 201 Created

{
  "id": 42,
  "title": "Nowe godziny otwarcia",
  "status": "approved",
  "created_at": "2026-01-02T10:42:00Z"
}
```

---

## 🔄 Przykład 2: Zatwierdzanie Ogłoszenia

### Scenariusz: Admin zatwierdza oczekujące ogłoszenie

```
HTTP POST /api/announcements/{id}/approve
```

### Controller

```php
public function approve(Request $request, int $id): Response {
    try {
        $useCase = $this->container->get(ApproveAnnouncementUseCase::class);
        $announcementDTO = $useCase->execute(
            announcementId: $id,
            adminId: $request->userId()
        );
        
        return Response::ok([
            'message' => 'Announcement approved',
            'announcement' => $announcementDTO
        ]);
    } catch (AnnouncementNotFoundException $e) {
        return Response::notFound(['error' => 'Announcement not found']);
    } catch (AnnouncementException $e) {
        return Response::badRequest(['error' => $e->getMessage()]);
    }
}
```

### Use Case

```php
class ApproveAnnouncementUseCase {
    public function __construct(
        private AnnouncementRepository $repository
    ) {}
    
    public function execute(int $announcementId, int $adminId): AnnouncementDTO {
        // Pobierz z bazy
        $announcement = $this->repository->findById($announcementId);
        
        if (!$announcement) {
            throw new AnnouncementNotFoundException();
        }
        
        if ($announcement->status !== AnnouncementStatus::PENDING) {
            throw new AnnouncementException(
                "Cannot approve announcement that is not pending"
            );
        }
        
        // Aplikuj reguły biznesowe (logika w Domain!)
        $announcement->approve($adminId);
        
        // Zapisz
        $this->repository->save($announcement);
        
        // Zwróć DTO
        return AnnouncementDTO::fromEntity($announcement);
    }
}
```

### Domain Entity (Business Logic!)

```php
public function approve(int $decidedBy): void {
    // Business rule: Only PENDING can be approved
    // (implicit invariant, enforced by UseCase)
    
    $this->status = AnnouncementStatus::APPROVED;
    $this->decidedAt = new DateTimeImmutable();
    $this->decidedBy = $decidedBy;
}
```

---

## 🔐 Przykład 3: Rejestracja Użytkownika

### Controller

```php
public function register(Request $request): Response {
    try {
        $registerRequest = new RegisterUserRequest(
            email: $request->input('email'),
            password: $request->input('password'),
            name: $request->input('name'),
            phoneNumber: $request->input('phone_number')
        );
        
        $useCase = $this->container->get(RegisterUserUseCase::class);
        $userDTO = $useCase->execute($registerRequest);
        
        return Response::created(['user' => $userDTO]);
        
    } catch (ValidationException $e) {
        return Response::unprocessableEntity(['errors' => $e->getErrors()]);
    } catch (UserException $e) {
        return Response::badRequest(['error' => $e->getMessage()]);
    }
}
```

### Use Case

```php
class RegisterUserUseCase {
    public function __construct(
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher
    ) {}
    
    public function execute(RegisterUserRequest $request): UserDTO {
        // Waliduj
        $errors = $request->validate();
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
        
        // Sprawdź czy email już istnieje
        if ($this->userRepository->existsByEmail($request->email)) {
            throw new UserException("Email already registered");
        }
        
        // Zahaszuj hasło (Infrastructure concern)
        $passwordHash = $this->passwordHasher->hash($request->password);
        
        // Stwórz Entity
        $user = new User(
            id: null,
            email: $request->email,
            passwordHash: $passwordHash,
            name: $request->name,
            phoneNumber: $request->phoneNumber,
            role: 'user' // Default role
        );
        
        // Zapisz
        $this->userRepository->save($user);
        
        // Zwróć DTO (bez hasła!)
        return UserDTO::fromEntity($user);
    }
}
```

### Request Object z Walidacją

```php
class RegisterUserRequest {
    public function __construct(
        public string  $email,
        public string  $password,
        public string  $name,
        public ?string $phoneNumber = null
    ) {}
    
    public function validate(): array {
        $errors = [];
        
        if (empty($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }
        
        if (strlen($this->password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        if (empty($this->name) || strlen($this->name) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }
        
        if ($this->phoneNumber && !preg_match('/^\d{9,}$/', $this->phoneNumber)) {
            $errors['phone'] = 'Invalid phone number format';
        }
        
        return $errors;
    }
}
```

---

## 📝 Przykład 4: Integracja z External API

### Scenariusz: CLI Command pobiera słowo dnia

### CLI Command (Presentation Layer)

```php
// src/Console/Command/FetchDailyWordCommand.php
class FetchDailyWordCommand {
    public function __construct(
        private Container $container,
        private LoggerInterface $logger
    ) {}
    
    public function execute(): void {
        try {
            $useCase = $this->container->get(FetchAndSaveWordUseCase::class);
            $wordDTO = $useCase->execute();
            
            echo "✓ Word of the day saved: {$wordDTO->word}\n";
            $this->logger->info("Daily word fetched", ['word' => $wordDTO->word]);
            
        } catch (ExternalServiceException $e) {
            echo "✗ Failed to fetch word: {$e->getMessage()}\n";
            $this->logger->error("Failed to fetch daily word", ['error' => $e]);
            exit(1);
        }
    }
}
```

### Use Case

```php
class FetchAndSaveWordUseCase {
    public function __construct(
        private ExternalWordApiService $wordService,
        private WordRepository $wordRepository
    ) {}
    
    public function execute(): WordDTO {
        // Get from external API (Infrastructure)
        $word = $this->wordService->fetchDailyWord();
        
        // Save to DB (Infrastructure)
        $this->wordRepository->save($word);
        
        // Return DTO
        return WordDTO::fromEntity($word);
    }
}
```

### External Service

```php
class ExternalWordApiService {
    public function __construct(
        private HttpClient $client,
        private string $apiKey
    ) {}
    
    public function fetchDailyWord(): Word {
        try {
            // HTTP Call
            $response = $this->client->get(
                'https://api.wordapi.com/daily',
                ['Authorization' => "Bearer {$this->apiKey}"]
            );
            
            if ($response->status() !== 200) {
                throw new ExternalServiceException(
                    "API returned status {$response->status()}"
                );
            }
            
            $data = json_decode($response->body(), assoc: true);
            
            // Validate response structure
            if (!isset($data['word']) || !isset($data['definition'])) {
                throw new InvalidResponseException("Missing required fields");
            }
            
            // Transform to Domain Entity
            return new Word(
                id: null,
                word: $data['word'],
                definition: $data['definition'],
                example: $data['example'] ?? '',
                language: $data['language'] ?? 'en'
            );
            
        } catch (HttpException $e) {
            throw new ExternalServiceException("Failed to fetch word", cause: $e);
        }
    }
}
```

---

## 🛡️ Przykład 5: Error Handling

### Hierarchia Wyjątków

```php
// Domain Layer
namespace App\Domain\Exception;

abstract class DomainException extends Exception {}
class AnnouncementException extends DomainException {}
class UserException extends DomainException {}

// Application Layer
namespace App\Application\Exception;

class ValidationException extends Exception {
    public function __construct(
        private array $errors
    ) {}
    
    public function getErrors(): array {
        return $this->errors;
    }
}

// Infrastructure Layer
namespace App\Infrastructure\Exception;

class DatabaseException extends Exception {}
class ExternalServiceException extends Exception {}
```

### Controller - Comprehensive Error Handling

```php
public function create(Request $request): Response {
    try {
        // Happy path
        $request = new CreateAnnouncementRequest(...);
        $useCase = $this->container->get(CreateAnnouncementUseCase::class);
        $dto = $useCase->execute($request);
        
        return Response::created(['data' => $dto]);
        
    } catch (ValidationException $e) {
        // Client error - bad input
        return Response::unprocessableEntity([
            'error' => 'Validation failed',
            'errors' => $e->getErrors()
        ]);
        
    } catch (AnnouncementException | UserException $e) {
        // Business logic error
        return Response::badRequest([
            'error' => $e->getMessage()
        ]);
        
    } catch (DatabaseException $e) {
        // Infrastructure error
        $this->logger->error("Database error", ['error' => $e]);
        return Response::internalServerError([
            'error' => 'Failed to save announcement'
        ]);
        
    } catch (Exception $e) {
        // Unexpected error
        $this->logger->critical("Unexpected error", ['error' => $e]);
        return Response::internalServerError([
            'error' => 'Internal server error'
        ]);
    }
}
```

---

## 📊 Diagram Flow - Kompletny Scenariusz

```
USER
  ↓
HTTP POST /api/announcements
  ↓
┌──────────────────────────────┐
│   PRESENTATION LAYER         │
│  (AnnouncementController)    │
├──────────────────────────────┤
│ 1. Parse JSON                │
│ 2. Create Request object     │
│ 3. Validate request          │
│ 4. Get UseCase from DI       │
│ 5. Execute UseCase           │
│ 6. Format Response           │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│   APPLICATION LAYER          │
│ (CreateAnnouncementUseCase)  │
├──────────────────────────────┤
│ 1. Validate request          │
│ 2. Call factory to create    │
│    domain entity             │
│ 3. Call repository.save()    │
│ 4. Call DTO.fromEntity()     │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│   DOMAIN LAYER               │
│   (Announcement Entity)       │
├──────────────────────────────┤
│ - Apply business rules       │
│ - Ensure invariants          │
│ - Return to UseCase          │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│ INFRASTRUCTURE LAYER         │
│ (DatabaseAnnouncementRepository)
├──────────────────────────────┤
│ 1. SQL INSERT                │
│ 2. Update ID                 │
│ 3. Return to UseCase         │
│                              │
│ (Database)                   │
│ announcements table          │
└──────────────┬───────────────┘
               ↓
           HTTP 201
         (JSON DTO)
             ↓
           USER
```

---

## 🎯 Szybka Referencja Patternów

### Tworzenie Entity
```php
// Factory method w Entity
$entity = Announcement::createNew(...);

// Lub Factory pattern
$entity = AnnouncementFactory::createFromRequest($request);
```

### Zapisanie Entity
```php
$repository->save($entity); // INSERT lub UPDATE
```

### Pobranie Entity
```php
$entity = $repository->findById($id);     // Jedno
$entities = $repository->findAll();       // Wiele
$entities = $repository->findByStatus(); // Filtr
```

### Transformacja do DTO
```php
$dto = AnnouncementDTO::fromEntity($entity);
```

### Obsługa Błędów
```php
if ($errors = $request->validate()) {
    throw new ValidationException($errors);
}

if (!$entity) {
    throw new EntityNotFoundException();
}

$entity->businessMethod(); // Rzuca DomainException jeśli invariant naruszony
```
