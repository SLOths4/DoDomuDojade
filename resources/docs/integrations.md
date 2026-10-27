# Integracje zewnętrzne

Wszystkie integracje używają jednego klienta Symfony HttpClient. **Nie ustawiono timeoutów ani ponowień** — obowiązują wartości domyślne (~60 s).

## Tramwaje (ZTM Poznań, PEKA)

Plik: `src/Infrastructure/ExternalApi/Tram/TramService.php`.

- `POST TRAM_URL`, `Content-Type: application/x-www-form-urlencoded`, body `method=getTimes&p0={"symbol":"<STOP_ID>"}`.
- `stopId` musi pasować do `^[A-Z0-9]+$`.
- Odpowiedź:
    - `success === false` → błąd `apiLogicError(error.message)`,
    - `success` nie jest obiektem → `noDepartureData`,
    - brak `success.times` → pusta lista,
    - inaczej `success.times[] = {line, minutes, direction, …}`.
- `GetDeparturesUseCase` odpytuje każdy przystanek z `STOP_ID`, mapuje na `{stopId, line, minutes, direction}`, łączy i sortuje po `minutes`. Błąd jednego przystanku jest logowany i pomijany.
- Brak cache — każde odświeżenie ekranu (co 60 s) to N zapytań do PEKA.
- Metody `getStops`, `getLines`, `getRoutes`, `findMessagesForBollard` są zaimplementowane, ale nieużywane.

!!! note "Oficjalne źródło"
    `method.vm` to nieoficjalne obejście. ZTM udostępnia [otwarte dane](https://www.ztm.poznan.pl/otwarte-dane/) w formacie GTFS (rozkład) i GTFS-RT (opóźnienia), bez klucza API. Kolumna `stop_code` w `stops.txt` zawiera te same symbole przystanków co PEKA (`AWF41`). Nowa wersja korzysta z tych danych — patrz [plan migracji](python-migration.md#tramwaje-gtfs-gtfs-rt-ztm-poznan).

## Pogoda (IMGW + Airly)

Pliki: `src/Infrastructure/ExternalApi/Weather/WeatherService.php`, `src/Application/Display/GetDisplayWeatherUseCase.php`.

**IMGW**: `GET IMGW_WEATHER_URL`. Używane pola: `stacja`, `data_pomiaru`, `godzina_pomiaru`, `temperatura`, `predkosc_wiatru`, `kierunek_wiatru`, `wilgotnosc_wzgledna`, `suma_opadu`, `cisnienie`.

**Airly**: `GET {AIRLY_ENDPOINT}?locationId={AIRLY_LOCATION_ID}`, nagłówki `apikey: AIRLY_API_KEY`, `Accept-Language: pl`.

- Wartości są czytane **po indeksie tablicy** (`current.values[0]` = PM1, `[1]` = PM2.5, `[3]` = PM10, …) — kolejność nie jest gwarantowana przez Airly.
- `current.indexes[0]` → `{value, level, color, description, advice}`.
- Błąd Airly jest połykany (pola `null`).

**Cache** (tabela `weather`, TTL `WEATHER_CACHE_TTL_SECONDS`):

1. Weź najnowszy snapshot; jeśli świeży — zwróć.
2. Inaczej odpytaj API; przy błędzie zwróć przeterminowany snapshot albo `null`.
3. Po sukcesie zapisz nowy snapshot (błąd zapisu → tylko warning).

Wynik dla ekranu: `{temperature, pressure, airlyAdvice, airlyDescription, airlyColour}` (stringi, `"N/A"` przy braku).

## Kalendarz Google

Plik: `src/Infrastructure/ExternalApi/Calendar/CalendarService.php`.

- `google/apiclient`, konto serwisowe z pliku `CALENDAR_API_KEY_PATH`, scope `calendar.readonly`.
- `events.list(CALENDAR_ID, timeMin = dziś 00:00, timeMax = jutro 00:00, orderBy = startTime, singleEvents = true)`.
- `CalendarService` (implementuje `CalendarServiceInterface`) mapuje wydarzenia na obiekty `CalendarEvent`: `{summary, description, start: "d.m.Y H:i", end: "H:i"}`. Brak opisu daje pusty string.
- Każdy wyjątek → `null` (moduł pusty).
- Brak cache — wywołanie Google API przy każdym odświeżeniu (co 40 s).

!!! bug
    Wydarzenia całodniowe mają tylko `start.date`. Od października 2026 kod używa tej daty (wcześniej pokazywał bieżącą godzinę), ale wynik to `start: "dd.mm.rrrr 00:00"`, `end: "00:00"` — bez oznaczenia „cały dzień”.

## Cytat dnia

!!! danger "Nie działa"
    `quotes-api-self.vercel.app` zwraca `404 DEPLOYMENT_NOT_FOUND` (sprawdzone 2026-10-27). Zamienniki: [plan migracji](python-migration.md#cytat-dnia).

- `GET QUOTE_API_URL` → wymagane pola `quote`, `author`.
- Wywoływane tylko z CLI `quote:fetch`, zapis do tabeli `quote`, publikacja zdarzenia `quote.created`.

## Słowo dnia

!!! warning
    `api.wotd.site` działało w dniu analizy, ale to prywatny serwis bez gwarancji; odrzuca daty z przyszłości (liczy w UTC). Zapasowe źródła: [plan migracji](python-migration.md#sowo-dnia).

- `GET WORD_API_URL + YYYY-MM-DD` (data w **UTC**) → wymagane pola `word`, `ipa`, `definition`.
- Wywoływane tylko z CLI `word:fetch`, zapis do tabeli `word` (bez zdarzenia).
- Sprawdzenie „czy już pobrano dziś” używa lokalnej strefy serwera, a URL — UTC.

## Nieużywane zależności

- `phpmailer/phpmailer` — w `composer.json`, brak użycia w kodzie.
- `alpinejs` — w `package.json`, ale ładowany z CDN.
