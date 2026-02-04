# Development

## Jak rozwijać dalej projekt?
Zacznij od tego, żeby się zapoznać z dokumentacją. 
Projekt kieruje się następującymi wartościami: **prostotą**, **jakością**.

Aby dodać nowy moduł/funkcjonalność, zadbaj o to, żeby w odpowiednich warstwach znalazł się odpowiedni kod, analogiczny do tego, który działa w pozostałych modułach.


## 🎓 Best Practices

### Domain Layer
✅ DO:

- Implementuj reguły biznesowe
- Używaj Value Objects
- Zwracaj Entity z metodami
- Definiuj wyjątki domenowe

❌ DON'T:

- Nie importuj Infrastructure
- Nie rób SQL queries
- Nie parsuj JSON/XML
- Nie loguj (przynajmniej nie w core)

### Application Layer
✅ DO:

- Orkiestruj UseCase
- Waliduj DTO-s
- Transformuj między Domain a Presentation

❌ DON'T:

- Nie implementuj reguł biznesowych
- Nie bezpośrednio korzystaj z bazy
- Nie mieszaj logiki różnych Use Cases

### Infrastructure Layer
✅ DO:

- Implementuj interfejsy repozytoriów z Domain
- Integruj z zewnętrznymi serwisami
- Zarządzaj bazą danych
- Konfiguruj zależności

❌ DON'T:

- Nie implementuj reguł biznesowych
- Nie używaj Domain bezpośrednio w SQL
- Nie twórz Service Locator (używaj DI)

### Presentation Layer
✅ DO:

- Parsuj HTTP requests
- Waliduj input
- Deleguj do Use Cases
- Formatuj responses

❌ DON'T:

- Nie implementuj logiki biznesowej
- Nie dostępuj bezpośrednio do bazy
- Nie rób żadnych transformacji Entity

## 🔗 Relacje Między Warstwami

```
Domain Layer
    ↑
    │ depends on (implements interface)
    │
Application Layer
    ↑
    │ depends on (calls)
    │
Presentation Layer (Http, Console)
    
    
Infrastructure Layer
    │ implements
    ↓
Domain Layer (interfaces only)
```

**Kluczowa Reguła**: Infrastructure NIGDY nie importuje Application, Application importuje Domain interfaces które są implementowane w Infrastructure.

## 📚 Namespace Mapping

| Warstwa        | Namespace                    | Przykład                                                       |
|----------------|------------------------------|----------------------------------------------------------------|
| Domain         | `App\Domain\`                | `App\Domain\Announcement\Announcement`                         |
| Application    | `App\Application\`           | `App\Application\Announcement\UseCase\CreateAnnouncementUseCase`  |
| Infrastructure | `App\Infrastructure\`        | `App\Infrastructure\Persistence\PDOAnnouncementRepository`     |
| Presentation   | `App\Presentation\` / `App\Console\` | `App\Presentation\Http\Controller\AnnouncementController` |


## 🚀 Rozszerzanie Projektu

Aby dodać nową funkcjonalność (np. nowy moduł):

1. **Stwórz Entity w Domain**

```php
// src/Domain/NewEntity/NewEntity.php
final class NewEntity { }
```

2. **Zdefiniuj Enums (jeśli potrzebne)**
  
```php
// src/Domain/NewEntity/NewEntityStatus.php
enum NewEntityStatus { }
```

3. **Stwórz Use Cases w Application**
```php
// src/Application/NewEntity/UseCase/CreateNewEntityUseCase.php
class CreateNewEntityUseCase { }
```

4. **Implementuj Repository w Infrastructure (jeśli potrzebne)**

```php
// src/Infrastructure/Persistence/PDONewEntityRepository.php
class PDONewEntityRepository { }
```

5. **Zarejestruj w DI Container**
```php
// src/Infrastructure/Container.php
$container->set(NewEntityRepository::class, fn(Container $c) => $implementation);
```
