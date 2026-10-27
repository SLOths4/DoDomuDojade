# Display — ekran informacyjny

Podstrona `/display` stanowi główną funkcjonalność aplikacji. To od niej zaczął się nasz projekt :-)

`GET /display` renderuje szablon `pages/display` bez danych z serwera — wszystko ładuje się po stronie przeglądarki z endpointów `GET /display/*`.

## Endpointy

| Endpoint | Moduł | Payload `data` | Odświeżanie w przeglądarce |
|---|---|---|---|
| `/display/departure` | `tram` | `[{stopId, line, minutes, direction}]` | co 60 s |
| `/display/weather` | `weather` | `{temperature, pressure, airlyAdvice, airlyDescription, airlyColour}` | co 60 s |
| `/display/announcement` | `announcement` | `[{title, author, text}]` | co 8 s |
| `/display/countdown` | `countdown` | `{title, count_to}` (unix timestamp) | co 40 s |
| `/display/event` | `calendar` | `[{summary, description, start, end}]` | co 40 s |
| `/display/quote` | `quote` | `{from, quote}` | co 40 s |
| `/display/word` | `word` | `{word, ipa, definition}` | co 40 s |

Żadne z nich nie wymaga uwierzytelnienia.

## Format odpowiedzi

Wszystkie endpointy zwracają HTTP 200 i nagłówek `X-Request-Id`:

```json
{
  "status": "active",
  "data": { "...": "..." },
  "message": null,
  "request_id": "9f2c1a7b3e4d5f60"
}
```

| `status` | Znaczenie | `data` | `message` |
|---|---|---|---|
| `active` | Moduł włączony i są dane | payload | `null` |
| `empty` | Moduł włączony, brak danych (`null`, `[]`, pusty string) | `null` | komunikat |
| `inactive` | Moduł wyłączony lub poza oknem czasowym | `null` | komunikat (po polsku) |

Przy nieoczekiwanym błędzie (np. brak wiersza modułu w tabeli `module`) zwracana jest [koperta błędu](api.md#formaty-odpowiedzi) z kodem 500.

## Algorytm każdego endpointu

1. `IsModuleVisibleUseCase(ModuleName)` — moduł musi istnieć, mieć `is_active = true` i `start_time <= teraz <= end_time`.
2. Niewidoczny → `inactive`.
3. Use case modułu (`GetDeparturesUseCase`, `GetDisplayWeatherUseCase`, …).
4. Pusty wynik → `empty`, inaczej `active`.

## Przykłady

### `/display/departure`

```json
{
  "status": "active",
  "data": [
    {"stopId": "AWF41", "line": "18", "minutes": 0, "direction": "Ogrody"},
    {"stopId": "AWF05", "line": "190", "minutes": 3, "direction": "Os. Sobieskiego"}
  ],
  "message": null,
  "request_id": "…"
}
```

### `/display/weather`

```json
{
  "status": "active",
  "data": {
    "temperature": "-0.7",
    "pressure": "1002.6",
    "airlyAdvice": "N/A",
    "airlyDescription": "N/A",
    "airlyColour": "N/A"
  },
  "message": null,
  "request_id": "…"
}
```

### `/display/event`

```json
{
  "status": "active",
  "data": [
    {"summary": "Zebranie", "description": null, "start": "27.10.2026 21:30", "end": "22:30"}
  ],
  "message": null,
  "request_id": "…"
}
```

### `/display/countdown`

```json
{"status": "active", "data": {"title": "Ferie", "count_to": 1769731200}, "message": null, "request_id": "…"}
```

### `/display/word`, `/display/quote`

```json
{"status": "active", "data": {"word": "mirative", "ipa": "/ˈmɪɹətɪv/", "definition": "…"}, "message": null, "request_id": "…"}
```

```json
{"status": "active", "data": {"from": "Clare", "quote": "…"}, "message": null, "request_id": "…"}
```

### Moduł wyłączony

```json
{"status": "inactive", "data": null, "message": "Moduł odjazdów jest wyłączony.", "request_id": "…"}
```

## Frontend ekranu

`pages/display.twig` to samodzielny dokument HTML (bez `extends`) z komponentami Alpine.js zdefiniowanymi inline:

| Komponent | Rola |
|---|---|
| `layout()` | Dopasowuje wysokość siatki do okna. |
| `clock()` | Data i godzina `pl-PL`, co 1 s. |
| `weather()` | `/display/weather`, kolor temperatury wg progów -10/0/5/15/25 °C. |
| `tramDepartures()` | `/display/departure`, kolory linii, `0` min → `<1`, > 60 min → `Xh Y`. |
| `multiModuleRotator()` | Równolegle `/countdown`, `/quote`, `/word`, `/event`; slajdy zmieniane co 10 s. |
| `announcements()` | `/display/announcement`, jedno ogłoszenie co 8 s. |

Wspólny helper `parseDisplayResponse(res)` zamienia błędny JSON lub `!res.ok` na `status: 'error'`.

Zależności z CDN: Alpine.js, FontAwesome, Google Fonts (Lato). Style: Tailwind CSS 4 (`src/input.css` → `public/assets/styles/output.css`).
