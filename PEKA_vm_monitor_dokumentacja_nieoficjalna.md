### Przewodnik: Jak połączyć się z Wirtualnym Monitorem PEKA i korzystać z jego funkcji

**1. Podstawowe informacje o API:**

- **Adres API:**  
  Wszystkie żądania kieruj pod adres:  
  ```
  https://www.peka.poznan.pl/vm/method.vm
  ```

- **Metoda HTTP:**  
  API używa metody POST.

- **Nagłówki:**  
  Konieczny jest nagłówek:  
  ```
  Content-Type: application/x-www-form-urlencoded
  ```

- **Parametry żądania:**  
  API wymaga dwóch podstawowych parametrów:
  - **method**: Określa, jaką operację chcemy wykonać (np. pobrać rozkład jazdy, sprawdzić przystanki).
  - **p0**: Parametry dla wybranej metody, zapisane jako JSON.

---

**2. Opcje dostępne w API:**

| **Metoda**      | **Opis**                                                                 | **Przykład p0**                                                                                   |
|------------------|---------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------|
| `getTimes`      | Zwraca rzeczywiste czasy odjazdów dla danego przystanku.                 | `{"symbol":"STOP_ID"}`                                                                           |
| `getStops`      | Zwraca listę przystanków w okolicy wskazanej lokalizacji GPS.            | `{"lat":52.406374,"lon":16.925168}`                                                              |
| `getLines`      | Zwraca szczegóły dotyczące konkretnej linii, np. jej trasę.              | `{"line":"NUMBER"}`                                                                              |
| `getRoutes`     | Pobiera wszystkie trasy obsługiwane przez wybraną linię.                 | `{"line":"NUMBER"}`                                                                              |

---

**3. Przykład zapytania:**

Chcemy sprawdzić czasy odjazdów dla przystanku o symbolu "STOP_ID".  

**Zapytanie:**  
- **method:** `getTimes`  
- **p0:** `{"symbol":"STOP_ID"}`  

**Kod JSON (body):**  
```
method=getTimes&p0={"symbol":"STOP_ID"}
```

**Odpowiedź:**  
API zwróci listę odjazdów, np.:
```json
[
  {
    "line": "12",
    "direction": "Dworzec Główny",
    "departure": "2025-01-16T15:30:00"
  },
  {
    "line": "5",
    "direction": "Rataje",
    "departure": "2025-01-16T15:35:00"
  }
]
```

---

**4. Szybki przewodnik dla innych metod:**

### Metoda `getStops`
**Opis:** Pobiera listę przystanków w okolicy.  
**Parametr `p0`:** Współrzędne GPS w formacie JSON, np.:  
```json
{"lat":52.406374,"lon":16.925168}
```

### Metoda `getLines`
**Opis:** Zwraca informacje o linii, np. trasę i godziny.  
**Parametr `p0`:** Numer linii, np.:  
```json
{"line":"12"}
```

### Metoda `getRoutes`
**Opis:** Pobiera trasy dostępne dla wskazanej linii.  
**Parametr `p0`:** Numer linii, np.:  
```json
{"line":"5"}
```

---

**5. Kluczowe uwagi:**
- Symbol przystanku (`STOP_ID`) możesz znaleźć, korzystając z metody `getStops`.
- Numery linii (`line`) znajdziesz w rozkładach jazdy w Poznaniu.
- API nie wymaga autoryzacji, ale korzystanie z niego powinno być zgodne z zasadami fair use.

Masz pytania lub potrzebujesz wyjaśnień? 😊
