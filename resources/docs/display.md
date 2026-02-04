# API Reference — DoDomuDojadę

Podstrona `display` stanowi główną funkcjonalność aplikacji. To od niej zaczął się nasz projekt :-) (Nie wierzysz? Zajrzyj w przeszłość w repozytorium!)

## 📡 Endpoints Overview

| Endpoint                     | Method | Type | Purpose                | Auth |
|------------------------------|--------|------|------------------------|------|
| `/display`                   | GET    | HTML | Główna strona display  | No   |
| `/display/departure`         | GET    | JSON | Odjazdy tramwajów      | No   |
| `/display/announcement`      | GET    | JSON | Ogłoszenia             | No   |
| `/display/countdown`         | GET    | JSON | Odliczanie             | No   |
| `/display/weather`           | GET    | JSON | Pogoda                 | No   |
| `/display/event`             | GET    | JSON | Zdarzenia z kalendarza | No   |
| `/display/quote`             | GET    | JSON | Cytat dnia             | No   |
| `/display/word`              | GET    | JSON | Słowo dnia             | No   |

> Wszystkie endpoint-y Display zwracają spójny format z polem `is_active`, które mówi, czy dany moduł jest obecnie włączony

---

## 🏗️ Display API Endpoints

> Jeżeli dany moduł nie zwrócił danych, pole, które normalnie by je zawierało będzie równe `null`.

### GET `/display/departure`

Pobiera odjazdy dla skonfigurowanych przystanków.

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

### GET `/display/announcement`

Pobiera ważne (valid) ogłoszenia.

**Przykładowa odpowiedź (aktywny moduł):**
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

Pobiera pogodę.

**Przykładowa odpowiedź (aktywny moduł):**
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

### GET `/display/countdown`

Pobiera najświeższe odliczanie.

**Przykładowa odpowiedź (aktywny moduł):**
```json
{
  "is_active":true,
  "countdown": {
    "title":"tytuł",
    "count_to":1769731200
  }
}
```

### GET `/display/event`

Pobiera zdarzenia z kalendarza.

**Przykładowa odpowiedź (aktywny moduł):**
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

Pobiera słowo dnia (z bazy danych).

**Przykładowa odpowiedź (aktywny moduł):**

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

Pobiera cytat dnia (z bazy danych).

**Przykładowa odpowiedź (aktywny moduł):**

```json
{
  "is_active":true,
  "quote": {
    "from":"Clare",
    "quote":"Don\u2019t be so quick to throw away your life. No matter how disgraceful or embarrassing it may be, you need to keep struggling to find your way out until the very end."
  }
}
```
