# Development

## Jak rozwijać dalej projekt?
Zacznij od tego, żeby się zapoznać z dokumentacją. 
Projekt kieruje się następującymi wartościami: **prostotą**, **jakością**.

Aby dodać nowy moduł/funcjonalność zadbaj o to, żeby w odpowiednich warstwach znalazł sięodpowiedni kod, analogiczny do tego działający dla pozostałych modułów.


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

- Implementuj Repository interfaces
- Integruj z zewnętrznymi serwisami
- Zarządzaj baza danych
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
| Domain         | `App\Domain\`                | `App\Domain\Entity\Announcement`                               |
| Application    | `App\Application\`           | `App\Application\UseCase\CreateAnnouncementUseCase`            |
| Infrastructure | `App\Infrastructure\`        | `App\Infrastructure\Repository\DatabaseAnnouncementRepository` |
| Presentation   | `App\Http\` / `App\Console\` | `App\Http\Controller\AnnouncementController`                   |


## 🚀 Rozszerzanie Projektu

Aby dodać nową funkcjonalność (np. nowy moduł):

1. **Stwórz Entity w Domain**

```php
// src/Domain/Entity/NewEntity.php
final class NewEntity { }
```

2. **Zdefiniuj Enums (jeśli potrzebne)**
  
```php
// src/Domain/Enum/NewEntityStatus.php
enum NewEntityStatus { }
```

3. **Stwórz Use Cases w Application**
```php
// src/Application/UseCase/CreateNewEntityUseCase.php
class CreateNewEntityUseCase { }
```

4. **Implementuj Repository w Infrastructure (jeśli potrzebne)**

```php
// src/Infrastructure/Repository/DatabaseNewEntityRepository.php
class DatabaseNewEntityRepository { }
```

5. **Zarejestruj w DI Container**
```php
// src/Infrastructure/Container.php
$container->register(NewEntityRepository::class, $implementation);
```