# Konfiguracja (`.env`)

Konfigurację wczytuje `App\Infrastructure\Configuration\Config::fromEnv()`. Puste wartości traktowane są jak brak zmiennej, wartości są trimowane. Błąd konfiguracji pojawia się przy pierwszym użyciu `Config` jako `ConfigException` (500).

Przy starcie sprawdzane jest, że `ANNOUNCEMENT_MIN_*_LENGTH < ANNOUNCEMENT_MAX_*_LENGTH`.

## Zmienne wymagane

| Zmienna | Opis |
|---|---|
| `LOGGING_DIRECTORY_PATH` | Katalog logów (gdy niezapisywalny — stdout). |
| `TWIG_CACHE_PATH` | Katalog cache Twig. |
| `IMGW_WEATHER_URL` | Endpoint IMGW synop, np. `https://danepubliczne.imgw.pl/api/data/synop/id/<station_id>`. |
| `AIRLY_ENDPOINT` | Endpoint Airly, np. `https://airapi.airly.eu/v2/measurements/location`. |
| `AIRLY_API_KEY` | Klucz API Airly. |
| `TRAM_URL` | Endpoint PEKA, np. `https://www.peka.poznan.pl/vm/method.vm`. |
| `CALENDAR_API_KEY_PATH` | Ścieżka do pliku JSON konta serwisowego Google. |
| `CALENDAR_ID` | Id kalendarza Google. |
| `QUOTE_API_URL` | Endpoint cytatu dnia. |
| `WORD_API_URL` | Prefiks endpointu słowa dnia (doklejana jest data `YYYY-MM-DD`). |

## Zmienne opcjonalne

| Zmienna | Domyślnie | Opis |
|---|---|---|
| `LOGGING_CHANNEL_NAME` | `APP` | Kanał Monolog. |
| `LOGGING_LEVEL` | `INFO` | W praktyce rozróżniane jest tylko `debug` / reszta. |
| `TWIG_DEBUG` | `false` | `true/1/yes/on`. |
| `AIRLY_LOCATION_ID` | `''` | Id lokalizacji Airly (wiodący `/` jest usuwany). |
| `WEATHER_DATE_FORMAT` | `Y-m-d H:i:s` | |
| `WEATHER_CACHE_TTL_SECONDS` | `900` | TTL cache pogody. |
| `DB_HOST` | `localhost` | |
| `DB_PORT` | `5432` | |
| `DB_NAME` | `dodomudojade` | |
| `DB_USERNAME`, `DB_PASSWORD` | `''` | W praktyce wymagane. |
| `ANNOUNCEMENT_DATE_FORMAT` | `Y-m-d` | |
| `ANNOUNCEMENT_MAX_VALID_DATE` | `+1 year` | Wyrażenie PHP `modify()`. |
| `ANNOUNCEMENT_DEFAULT_VALID_DATE` | `+30 days` | Ważność propozycji bez daty. |
| `ANNOUNCEMENT_MIN_TITLE_LENGTH` / `MAX` | `5` / `255` | |
| `ANNOUNCEMENT_MIN_TEXT_LENGTH` / `MAX` | `10` / `65535` | |
| `COUNTDOWN_DATE_FORMAT` | `Y-m-d H:i:s` | |
| `COUNTDOWN_MIN_TITLE_LENGTH` | `3` | |
| `COUNTDOWN_MAX_TITLE_LENGTH` | `255` | |
| `MODULE_DATE_FORMAT` | `H:i:s` | |
| `USER_DATE_FORMAT` | `Y-m-d` | |
| `MAX_USERNAME_LENGTH` | `255` | |
| `MIN_PASSWORD_LENGTH` | `8` | |
| `STOP_ID` | `''` | Lista przystanków po przecinku, np. `AWF41,AWF05`. |
| `QUOTE_DATE_FORMAT` | `Y-m-d` | |
| `WORD_DATE_FORMAT` | `Y-m-d` | |

!!! note
    Nazwy tabel są od października 2026 stałymi w repozytoriach (`TABLE_NAME`); zmienne `*_TABLE_NAME` nie są już czytane. `.env.example` nadal zawiera nieużywane `USER_TABLE_NAME`, a nie zawiera większości zmiennych opcjonalnych (limitów długości, `WEATHER_*`, `ANNOUNCEMENT_*`, `COUNTDOWN_*`).

## Logi

Monolog `RotatingFileHandler` → `LOGGING_DIRECTORY_PATH/app-YYYY-MM-DD.log`; retencja 14 dni (debug) lub 7 dni (pozostałe).

## Cron (produkcja)

```cron
5 0 * * *  cd /ścieżka/do/app && bin/app quote:fetch
6 0 * * *  cd /ścieżka/do/app && bin/app word:fetch
0 3 * * 0  cd /ścieżka/do/app && bin/app announcement-rejected:delete $(date -d '30 days ago' +\%F)
```
