# DoDomuDojadę - Dokumentacja Architektura i API

Witaj w dokumentacji projektu **DoDomuDojadę**! Ta strona stanowi punkt wyjścia do zrozumienia struktury i architektury aplikacji.

## 📚 Struktura Dokumentacji

- **[API Documentation](layers/index.md)** - REST API layers
- **[Architecture](architecture/index.md)** - DDD architecture patterns
- **[Examples](examples/index.md)** - Practical usage examples

Dokumentacja jest podzielona na kilka kluczowych sekcji:

### [Architektura DDD](architecture/ddd.md)
Szczegółowy opis **Domain-Driven Design** architektura projektu, warstwy aplikacji, oraz przepływ danych między komponentami.

### [Domain Layer](layers/domain.md)
Opisuje warstwę domenową zawierającą:
- **Entities** - Główne obiekty biznesowe (Announcement, User, Word, Quote, Module, Countdown)
- **Value Objects** - Niezmienne obiekty wartości
- **Enums** - Wyliczenia dla typów i statusów
- **Exceptions** - Wyjątki domenowe

### [Application Layer](layers/application.md)
Dokumentacja warstwy aplikacji obejmująca:
- **Use Cases** - Główne scenariusze użytkownika
- **Data Transfer Objects (DTOs)** - Obiekty transferu danych
- **Services** - Orkiestracja logiki biznesowej

### [Infrastructure Layer](layers/infrastructure.md)
Szczegóły implementacji infrastruktury:
- **Repositories** - Dostęp do danych
- **External Services** - Integracje z zewnętrznymi API
- **Factories** - Tworzenie obiektów
- **Security** - Zabezpieczenia i autoryzacja
- **Helpers** - Funkcje wspierające

## 🎯 Cechy Projektu

### Stos Technologiczny
- **Backend**: PHP 8.3+ z czystą architekturą DDD
- **Framework**: Własna implementacja bez dużych frameworków
- **Baza Danych**: PostgreSQL, SQLite
- **Frontend**: HTML, CSS, JavaScript
- **Tools**: Composer, NPM, PHPStorm

### Kluczowe Moduły
1. **Announcements** - System ogłoszeń z workflow akceptacji
2. **Words** - Słownik dziennych słów
3. **Quotes** - Baza cytatów inspirujących
4. **Countdown** - Odliczanie do ważnych dat
5. **User Management** - Zarządzanie użytkownikami

## 🏗️ Architektura na Wysokim Poziomie

```
┌────────────────────────────────────────────────────────┐
│         Presentation Layer (Http, Console)             │
│              Controllers & CLI Commands                │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│         Application Layer (Use Cases, DTOs)             │
│              Business Logic Orchestration               │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│    Domain Layer (Entities, Value Objects)              │
│          Core Business Rules & Invariants              │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│  Infrastructure Layer (Repositories, Services)         │
│         External Services & Data Persistence           │
└────────────────────────────────────────────────────────┘
```

## 🚀 Szybki Start dla Developerów

### Zrozumienie Struktury
1. Zacznij od [Architecture Guide](architecture/ddd.md) aby zrozumieć DDD koncepty
2. Przejrzyj [Domain Layer](layers/domain.md) aby poznać core entities
3. Zbadaj [Application Layer](layers/application.md) aby zobaczyć use cases
4. Sprawdź [Infrastructure Layer](layers/infrastructure.md) dla detali implementacji

### Dodawanie Nowej Funkcjonalności
1. Zdefiniuj Entity w `src/Domain/Entity/`
2. Stwórz UseCase w `src/Application/UseCase/`
3. Implementuj Repository w `src/Infrastructure/Repository/`
4. Dodaj Controller w `src/Http/`
5. Napisz dokumentację zmian

## 📝 Konwencje Kodowania

### Namespace'y
```php
App\Domain\Entity       // Entity
App\Domain\ValueObject  // Value Objects
App\Domain\Enum        // Enumerations
App\Domain\Exception   // Exceptions

App\Application\UseCase        // Use Cases
App\Application\DataTransferObject  // DTOs

App\Infrastructure\Repository  // Repositories
App\Infrastructure\Service    // External Services
App\Infrastructure\Factory    // Factories
App\Infrastructure\Security   // Security Components

App\Http\Controller    // HTTP Controllers
App\Http\Response      // HTTP Responses

App\Console\Command    // CLI Commands
```

### Struktura Klas
- **Entities**: Mutable, z ID, reprezentują obiekty biznesowe
- **Value Objects**: Immutable, brak ID, reprezentują wartości
- **DTOs**: Transfer danych między warstwami
- **Services**: Operacje na danych, integracje zewnętrzne
- **Repositories**: Abstrakcja dostępu do danych

## 🔗 Powiązane Zasoby

- **Repository**: https://github.com/SLOths4/DoDomuDojade
- **Issues**: https://github.com/SLOths4/DoDomuDojade/issues
- **Kontakt**: sloths4@spolecznaczworka.pl
