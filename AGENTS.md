# AGENTS.md

Instrukcje dla agentów AI pracujących w repozytorium **DoDomuDojade**.

## Zakres
Ten plik obowiązuje dla całego repozytorium (katalog root i wszystkie podkatalogi).

## Cel zmian
- Priorytetem jest utrzymanie spójności między kodem i dokumentacją (`README.md`, `resources/docs/*.md`).
- Przy zmianach tras HTTP, middleware, endpointów API, komend CLI lub zmiennych `.env` **zawsze** zaktualizuj dokumentację.

## Szybki workflow
1. Zlokalizuj źródło prawdy w kodzie:
   - routing: `public/index.php`
   - składanie zależności: `src/Infrastructure/Container.php` oraz `src/Infrastructure/Container/Providers/`
   - komendy CLI: `src/Console/CommandRegistry.php`, `src/Console/Commands/`
   - konfiguracja runtime: `src/Infrastructure/Configuration/Config.php`
   - schemat DB: `schema/schema.sql` + `schema/migrations/*.sql`
2. Wprowadź minimalne, precyzyjne zmiany.
3. Uruchom adekwatne sprawdzenia (minimum: diff + test/lint jeśli dotyczy).
4. Zrób commit z jasnym komunikatem.

## Konwencje dokumentacji
- Pisz po polsku (zgodnie z aktualną dokumentacją projektu), techniczne nazwy i ścieżki zostawiaj po angielsku.
- Unikaj opisów „jak powinno być”; opisuj to, **jak jest w kodzie teraz**.
- Jeśli endpoint wymaga autoryzacji, dopisz to wprost.
- Jeśli endpoint/feature może zwrócić `null` albo fallback — opisz to jawnie.

## Konwencje zmian kodu
- Nie dodawaj zbędnych refaktorów przy zadaniach stricte dokumentacyjnych.
- Zachowuj istniejący styl i nazewnictwo.
- Nie dodawaj nowych zależności bez wyraźnej potrzeby.

## Kontrole jakości (dobierz do zakresu)
- Testy: `vendor/bin/phpunit`
- Analiza statyczna: `vendor/bin/phpstan analyse`
- Lokalny podgląd docs: `mkdocs serve`

Jeśli nie możesz uruchomić któregoś polecenia (np. brak zależności), odnotuj to w podsumowaniu.
