# DoDomuDojadę

[![GitHub last commit](https://img.shields.io/github/last-commit/SLOths4/DoDomuDojade)](https://github.com/SLOths4/DoDomuDojade/commits/main)  
[![GitHub issues](https://img.shields.io/github/issues/SLOths4/DoDomuDojade)](https://github.com/SLOths4/DoDomuDojade/issues)  
[![GitHub stars](https://img.shields.io/github/stars/SLOths4/DoDomuDojade?style=social)](https://github.com/SLOths4/DoDomuDojade/stargazers)

## O projekcie

Dodomudojadę to aplikacja webowa, która stanowi wirtualną tablicę informacyjną.

**Cele projektu:**
- Ułatwienie dostępu do informacji publicznych (transport, pogoda).
- Minimalistyczna architektura dla szybkiego development-u.
- Łatwość integracji z nowymi źródłami danych.

## Spis treści

- [Dokumentacja](#Dokumentacja)
- [Dostępne moduły](#Dostępne-moduły)
- [Stos technologiczny](#stos-technologiczny)
- [Szybki start (dev)](#Szybki-start-dev)
- [Szybki start (prod)](#Szybki-start-produkcja)
- [Rozwiązywanie problemów](#rozwiązywanie-problemów)
- [FAQ](#faq)
- [Autorzy](#Autorzy)

## Dokumentacja
Dokumentacja projektu znajduje się [tutaj](https://sloths4.github.io/DoDomuDojade/)
Autogenerowana dokumentacja kodu (klas, funkcji) znajduje się [tutaj](https://sloths4.github.io/DoDomuDojade/api/)

## Dostępne moduły
- **tramwaje**
- **ogłoszenia**
- **słowo dnia**
- **cytat dnia**
- **pogoda**
- **odliczanie**

## Stos technologiczny
### Backend
- PHP 8.4
### Frontend
- Tailwind CSS
- Alpine.js
### Baza danych
- Postgres (opcjonalnie, choć niezalecane: sqlite)

## Uruchamianie aplikacji
Sklonuj repozytorium:
   ```
   git clone https://github.com/SLOths4/DoDomuDojade.git
   cd DoDomuDojade
   ```

Utwórz bazę danych używając `schema.sql` i użytkownika (tutaj dla przykładu `ddd`)
```shell script
# 1. Connect as postgres (admin)
psql -U postgres

# 2. Create user (if doesn't exist)
create user ddd with password 'your_password';

# 3. Create database
create database dodomudojade owner ddd;

# 4. Exit
\q

# 5. Run schema as postgres
psql -U postgres -d dodomudojade -f schema/schema.sql

# 6. Check schema as ddd
psql -U ddd -d dodomudojade -c "\dt"

```

Dodatkowo pamiętaj, żeby nadać właściwe uprawnienia swojemu użytkownikowi. W przykładzie użytkownikiem tym jest `ddd`
```postgresql
-- Grant all privileges on database
grant all privileges on database dodomudojade to ddd;

-- Grant all privileges on all tables
grant all privileges on all tables in schema public to ddd;

-- Grant all privileges on all sequences (for auto-increment IDs)
grant all privileges on all sequences in schema public to ddd;

-- Grant usage on schema
grant usage on schema public to ddd;

-- Set default privileges for future tables
alter default privileges in schema public grant all on tables to ddd;
alter default privileges in schema public grant all on sequences to ddd;

-- Grant execute on functions (if any)
grant execute on all functions in schema public to ddd;

```

Nie można zapomnieć o dodaniu listy dostępnych modułów do tabeli modułów (standarodowo `module`)
```postgresql
insert into public.module (id, module_name, is_active, start_time, end_time)
values  (4, 'tram', true, '00:00', '23:59'),
        (5, 'weather', true, '00:00', '23:59'),
        (6, 'quote', true, '00:00', '23:59'),
        (7, 'word', true, '00:00', '23:59'),
        (1, 'announcement', true, '00:00', '23:59'),
        (3, 'countdown', true, '00:00', '23:59'),
        (2, 'calendar', true, '00:00', '23:59');
```

### Szybki start (dev)

Zainstaluj zależności:
   ```
   composer install --dev
   npm ci
   ```
Uruchom backend lokalnie (przykłady):
```shell script
php -S localhost:8080 -t public/ public/index.php
```
Skonfiguruj zmienne środowiskowe:
```shell script
cp .env.example .env
```
Zbuduj frontend:
```shell script
npm run dev
```

Jeżeli chcesz przeglądać dokumentację kodu, uruchom php Documentator za pomocą:
```shell script
vendor/bin/phpdoc run
```

Jeżeli chcesz zobaczyć dokumentację architektury etc.
```shell script
 mkdocs serve
```

### Szybki start (produkcja)
- Ustaw document root na `public/` (np. w Apache/Nginx).
- Konfiguruj PHP-FPM.
- Upewnij się, że `logs/` jest zapisywalne, ale nie publiczne.
- Zablokuj dostęp do katalogów źródłowych oraz .env.

#### Stwórz .env
```
cp .env.example .env
```
Ustaw `APP_ENV=prod` i `LOGGING_LEVEL=info`.

#### Ustaw cron jobs

##### Ustaw pobieranie dziennego słowa
```shell script
.bin/app word:fetch
```

##### Ustaw pobieranie dziennego cytatu
```shell script
.bin/app quote:fetch
```

##### Ustaw usuwanie odrzucanie ogłoszeń starszych niż podana data 
```shell script
.bin/app announcement-rejected:delete {YYYY-MM-DD}
```

## Rozwiązywanie problemów
Rozwiązanie większości problemów staje się oczywiste po spojrzeniu do logów. Dlatego zacznij właśnie tam. 

- **Błąd 500**: Sprawdź logi (`logs/app-{YYYY-MM-DD}.log`); sprawdź logi php fmp; włącz `APP_ENV=dev`.
- **Brak danych API**: Weryfikuj klucze w .env.
- **CSS się nie ładuje**: Uruchom `npm run build`; sprawdź ścieżki w HTML.
- **Baza się nie łączy**: Sprawdź PDO exceptions; testuj połączenie w teście.
- **Błąd bazy danych**: Sprawdź, czy baza danych ma prawidłową strukturę i zawiera wszystkie tabele; sprawdź, czy 

## FAQ

- **Dlaczego projekt nie używa framework-a?** Głównym celem jest lekkość projektu oraz nauka PHP.

## Licencja

Ten projekt jest licencjonowany na warunkach **CC-BY-NC-4.0** — [pełny tekst licencji](LICENSE).

Oznacza to, że:
- ✅ możesz używać, modyfikować i dzielić się kodem
- ✅ musisz przypisać autorstwo
- ❌ nie możesz użytkować komercyjnie kodu bez zgody

## Trochę o architekturze (Dla początkujących)
Projekt stara się utrzymać zgodność z architekturą DDD (Domain-Driven Design).

Punktem wejściowym całej aplikacji jest `index.php`. To tam znajdują się wszystkie ścieżki oraz ich obsługa.

Index zaczyna od inicjacji `bootstrap.php` w `src/bootstrap/bootstrap.php`. Tu z kolei dzieje się druga część magii. Wszystkie instancje klas są inicjowane, tak, żeby mogły potem zostać wykorzystane w DI (Dependency Injection).

Żeby wyjaśnić działanie aplikacji, przyjrzyjmy się przykładowej ścieżce `/login`.
1. Nasz serwer odpytuje `index.php` o tę ścieżkę
2. W router obecny w `index.php` odnajduje właściwą klasę i funkcję do uruchomienia. Jak to robi? Otóż w opisie ścieżki `$r->addRoute('GET', '/login', [PanelController::class, 'login']);` zawarta jest ta informacja.
3. Router uruchamia funkcję `login` w klasie `PanelController::class` (Dokładiej robi to w linii `$pipeline->run(fn() => $controller->$methodName($vars));`)
4. Funkcja login w akcji. (Poniżej przytaczam kod). Odziedziczona po `BaseController.php` funkcja render jest wykorzystywana do przekazania do użytkownika pliku z katalogu `src/Presentation`  
```
public function login(): void
    {
        try {
            $this->render('pages/login', [
                'footer' => true
            ]);
        } catch (Exception $e) {
            $this->handleError("Failed to load login page", "Login error: ".$e->getMessage());
        }
    }
```
Ot cała magia ✨

Warto dodać, że niektóre ścieżki zawierają tzw. "middleware". Jest ono częścią wspólną między różnymi warstwami aplikacji. W naszej aplikacji na tę chwilę znajduje się middleware odpowiedzialne za:
- csrf (cross-site request forgery)
- translacje
- uwierzytelnianie

No dobrze, dobrze. Rzeczywiście, jeżeli uważnie się przyjrzeć zawartości repozytorium to znajdziemy pare nieopisanych tutaj części. Większość z nich staje się całkiem oczywista po zrozumieniu wyżej opisanej, głównej koncepcji. Do niewymieniony, ale istonych elementów należą:
- Factory - *fabryki* tworzą insancje obiektów

*To be continued...*

## Autorzy

© **SLOths4** 2025

| Autor                 | GitHub                                       | Rola                                 |
|-----------------------|----------------------------------------------|--------------------------------------|
| Franciszek Kruszewski | [@Kruszewski](https://github.com/Kruszewski) | Full-stack development, architecture |
| Igor Woźnica          | [@hexer7](https://github.com/hexer7)         | Frontend, UI/UX                      |

---
**Masz pytania?** Otwórz [Issue](https://github.com/SLOths4/DoDomuDojade/issues) lub skontaktuj się z nami na [sloths4@spolecznaczworka.pl](mailto:sloths4@spolecznaczworka.pl).
