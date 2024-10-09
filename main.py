import sys
import requests
from PySide6.QtWidgets import QApplication, QMainWindow

def pobierz_odjazdy():
    # Dane do API
    url = "https://www.poznan.pl/mim/plan/map_service.html?mtype=pub_transport&co=cluster" # do zmiany ten adres 
    stop_id = "AWF" #id przystanku do zmiany

    params = {
        "method": "getDepartures",
        "id": "AWF73"
    }

    try:
        # Wysłanie zapytania do API PEKA
        response = requests.get(url, params=params)
        response.raise_for_status()  # Sprawdzanie, czy zapytanie się powiodło

        # Przetworzenie odpowiedzi JSON
        departures = response.json()
        
        # Wyświetlanie informacji o najbliższych odjazdach
        for departure in departures["departures"]:
            line = departure["line"]
            destination = departure["destination"]
            time = departure["departure"]
            print(f"Linia {line} do {destination} odjeżdża za {time} minut")
            
    except requests.RequestException as e:
        print("Błąd podczas pobierania danych:", e)

pobierz_odjazdy()

"""
# Tworzymy główną klasę aplikacji
class Okno(QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("DoDomuDojade")
        self.setGeometry(300, 300, 300, 400)

# Inicjalizacja aplikacji
if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = Okno()
    window.show()
    sys.exit(app.exec())

"""