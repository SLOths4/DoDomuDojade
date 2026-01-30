# DoDomuDojadę — Dokumentacja Architektura i API

Witaj w dokumentacji projektu **DoDomuDojade**! Ta strona stanowi punkt wyjścia do zrozumienia struktury i architektury aplikacji.

## 📚 Struktura Dokumentacji

- **[Autogenerowana dokumentacja kodu](code/)**
- **[Architektura](architecture.md)** — opisuje architekturę aplikacji
- **[Display](display.md)** — opisuje działanie najważniejszej funkcjonalności aplikacji`
- **[Development](development.md)** — opisuje założenia dalszego rozwoju projektu

## Stos technologiczny
### Backend

- PHP 8.5
### Frontend

- Tailwind CSS
- Alpine.js
### Baza danych

- Postgres

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

```shell
.bin/app word:fetch
```

##### Ustaw pobieranie dziennego cytatu

```shell
.bin/app quote:fetch
```

##### Ustaw usuwanie odrzucanie ogłoszeń starszych niż podana data

```shell
.bin/app announcement-rejected:delete {YYYY-MM-DD}
```

## Rozwiązywanie problemów
Rozwiązanie większości problemów staje się oczywiste po spojrzeniu do logów. Dlatego zacznij właśnie tam.

- **Błąd 500**: Sprawdź logi (`logs/app-{YYYY-MM-DD}.log`); sprawdź logi php fmp; włącz `APP_ENV=dev`.
- **Brak danych API**: Weryfikuj klucze w .env.
- **CSS się nie ładuje**: Uruchom `npm run build`; sprawdź, czy statyczne pliki są serwowane
- **Baza się nie łączy**: Upewnij się, że podałeś/aś poprawny username i hasło
- **Błąd bazy danych**: Sprawdź, czy baza danych ma prawidłową strukturę i zawiera wszystkie tabele;


## FAQ

- **Dlaczego projekt nie używa framework-a?** Głównym celem jest lekkość projektu oraz nauka PHP.

## Licencja

Ten projekt jest licencjonowany na warunkach **CC-BY-NC-4.0**

Oznacza to, że:

- ✅ możesz używać, modyfikować i dzielić się kodem
- ✅ musisz przypisać autorstwo
- ❌ nie możesz użytkować kodu komercyjnie bez zgody

## Autorzy

© **SLOths4** 2025

@AirScorpionK
@hexer7

---
**Masz pytania?** Otwórz [Issue](https://github.com/SLOths4/DoDomuDojade/issues) lub skontaktuj się z nami na [sloths4@spolecznaczworka.pl](mailto:sloths4@spolecznaczworka.pl).