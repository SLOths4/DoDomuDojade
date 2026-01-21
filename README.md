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

- [Dostępne moduły](#Dostępne-moduły)
- [Stos technologiczny](#stos-technologiczny)
- [Szybki start (dev)](#Szybki-start-dev)
- [Szybki start (prod)](#Szybki-start-produkcja)
- [Rozwiązywanie problemów](#rozwiązywanie-problemów)
- [FAQ](#faq)
- [Autorzy](#Autorzy)

## Dostępne moduły
- **tramwaje**
- **ogłoszenia**
- **słowo dnia**
- **cytat dnia**
- **pogoda**
- **odliczanie**

## Stos technologiczny
### Backend
- PHP 8.5
### Frontend
- Tailwind CSS
- Alpine.js
### Baza danych
- Postgres

## Uruchamianie aplikacji
Sklonuj repozytorium:
```shell scrpit
git clone https://github.com/SLOths4/DoDomuDojade.git
cd DoDomuDojade
```
Utwórz bazę danych używając `schema.sql` i użytkownika (tutaj dla przykładu `ddd`)
```shell script
# 1. Connect as postgres (admin)
psql -U postgres

# 2. Create user (if doesn't exist)
create user ddd with password '<your_password>';

# 3. Create database
create database dodomudojade owner ddd

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
Nie można zapomnieć o dodaniu listy dostępnych modułów do tabeli modułów (standardowo `module`)
```postgresql
insert into public.module (id, module_name, is_active, start_time, end_time)
values  (4, 'tram', true, '00:00', '23:59'),
        (5, 'weather', true, '00:00', '23:59'),
        (6, 'quote', true, '00:00', '23:59'),
        (7, 'word', true, '00:00', '23:59'),
        (1, 'announcements', true, '00:00', '23:59'),
        (3, 'countdown', true, '00:00', '23:59'),
        (2, 'calendar', true, '00:00', '23:59');
```
Dodaj nowego użytkownika przy użyciu interfejsu CLI. Pamiętaj, że nazwa użytkownika i haslo muszą byc zgodne z domyślnymi lub z ustalonymi przez Ciebie wymogami.

```shell
bin/app user:add <username> <password>
```

### Szybki start (dev)

Zainstaluj zależności:
   ```
   composer install --dev
   npm ci
   ```
Uruchom backend lokalnie np. przy użyciu wbudowanego serwera php:
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

Jeżeli chcesz zobaczyć dokumentację architektury etc.
```shell script
 mkdocs serve
```
Jeżeli chcesz przeglądać dokumentację kodu, autogenerowaną przy użyciu phpDocumentator użyj poniższej komendy:
```shell script
vendor/bin/phpdoc run
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

## Autorzy

© **SLOths4** 2025

@AirScorpionK
@hexer7

---
**Masz pytania?** Otwórz [Issue](https://github.com/SLOths4/DoDomuDojade/issues) lub skontaktuj się z nami na [sloths4@spolecznaczworka.pl](mailto:sloths4@spolecznaczworka.pl).