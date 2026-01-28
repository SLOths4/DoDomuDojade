# API Reference - DoDomuDojadę

Podstrona diplay stanowi główną funkcjonalność aplikacji. To od niej zaczą się nasz projekt :-) (Nie wierzysz? Zajżyj w przeszłość w repozytorium!)

## 📡 Endpoints Overview

| Endpoint                        | Method | Type | Purpose                 | Auth |
|---------------------------------|--------|------|-------------------------|------|
| `/display/departures`           | GET    | JSON | Rozkład jazdy tramwajów | No   |
| `/display/announcements`        | GET    | JSON | Ogłoszenia              | No   |
| `/display/countdown`            | GET    | JSON | Odliczanie              | No   |
| `/display/weather`              | GET    | JSON | Pogoda                  | No   |
| `/display/events`               | GET    | JSON | Zdarzenia z kalendarza  | No   |
| `/display/quote`                | GET    | JSON | Cytat dnia              | No   |
| `/display/word`                 | GET    | JSON | Słowo dnia              | No   |

> Wszystkie endpointy Display zwracają spójny format z polem `is_active`, które mówi, czy dany moduł jest obecnie włączony

---

## 🏗️ Display API Endpoints

> Jeżeli dany moduł nie zwrócił danych pole, które normalnie by je zawierało będzie równe `null`

### GET `/display/departures`

Pobiera odjazdy dla skonfigurowanych pojazdów.

**Przykładowa odpowiedź (aktywny moduł):**
```json
{
    "is_active":true,
    "departures":[
        {
            "stopId":"AWF41",
            "line":"18",
            "minutes":0,
            "direction":"Ogrody"
        },
        {
            "stopId":"AWF05",
            "line":"190",
            "minutes":0,
            "direction":"Os. Sobieskiego"
        }
    ]
}
```

### GET `/display/announcements`

```json
{
  "is_active":true,
  "announcements":[
    {
      "title":"tytuł",
      "author":"admin",
      "text":"treść"}
  ]
}
```

### GET `/display/weather`

```json
{
  "is_active":true,
  "weather": {
    "temperature":"-0.7",
    "pressure":"1002.6",
    "airlyAdvice":"N\/A",
    "airlyDescription":"N\/A",
    "airlyColour":"N\/A"
  }
}
```

### GET `/display/countdowns`

```json
{
  "is_active":true,
  "countdown": {
    "title":"tytuł",
    "count_to":1769731200
  }
}
```

### GET `/display/events`

```json
{
  "is_active":true,
  "events": [
    {
      "summary":"Wydarzenie ca\u0142odniowe",
      "description":"Wydarzenie bez opisu",
      "start":"Wydarzenie ca\u0142odniowe",
      "end":null
    },
    {
      "summary":"Testowe wydarzenie w ci\u0105gu dnia",
      "description":"Wydarzenie bez opisu",
      "start":"21:30",
      "end":"22:30"
    }
  ]
}
```

### GET `/display/word`

```json
{
  "is_active":true,
  "word": {
    "word":"mirative",
    "ipa":"\/\u02c8m\u026a\u0279\u0259t\u026av\/",
    "definition":"(countable, grammar) (An instance of) a form of a word which conveys this mood."
  }
}
```

### GET `/display/quote`

```json
{
  "is_active":true,
  "quote": {
    "from":"Clare",
    "quote":"Don\u2019t be so quick to throw away your life. No matter how disgraceful or embarrassing it may be, you need to keep struggling to find your way out until the very end."
  }
}
```