# Biblioteki, moduły, itp.
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from PySide6.QtWidgets import QApplication, QWidget, QVBoxLayout, QLabel, QHBoxLayout
from PySide6.QtGui import QPixmap
from PySide6.QtCore import QTimer, Qt
import time, sys, requests, json
from threading import Thread

# Set up Selenium WebDriver
options = webdriver.ChromeOptions()
options.add_argument("--headless")
driver = webdriver.Chrome(options = options, service=Service(ChromeDriverManager().install()))         

with open('data.json', 'r') as jsonfile:
    data = json.load(jsonfile)
    awf73 = data["API"][0]["url"]
    meteo = data["API"][1]["url"]
    app_style = data["stylesheet"][0]["style"]

class Tram_DataFetcher(Thread):
    def __init__(self, update_callback):
        super().__init__()
        self.update_callback = update_callback
        self.daemon = True

    def run(self):
        # Set up Selenium WebDriver
        options = webdriver.ChromeOptions()
        options.add_argument("--headless")
        driver = webdriver.Chrome(options=options, service=Service(ChromeDriverManager().install()))

        while True:
            driver.get(awf73)
            time.sleep(2)

            # Zmienne z danymi
            tramwaje = driver.find_elements(By.CLASS_NAME, "line")
            odjazdy = driver.find_elements(By.CLASS_NAME, "time")
            kierunki = driver.find_elements(By.CSS_SELECTOR, "div.direction")
            # Listy
            announcements_data = []
            data = []
            
            try:
                informacje_a = driver.find_element(By.CLASS_NAME, "messages_in")
                informacje = informacje_a.find_element(By.TAG_NAME, "a")
                announcements_data.append(informacje.text)
            except Exception as e:
                print("Nie znaleziono elementu. Przechodzę do następnej czynności:", e)

            for tramwaj, odjazd, kierunek in zip(tramwaje, odjazdy, kierunki):
                data.append(f"Tramwaj: {tramwaj.text}, Odjazd: {odjazd.text}, Kierunek: {kierunek.text}")

            # Update the UI
            self.update_callback(data, announcements_data)
            time.sleep(1)

        driver.quit()

class Meteo_DataFetcher(Thread):
    def __init__(self):
        super().__init__()
    
    def request(self):
        # The API endpoint
        url = "meteo"

        # A GET request to the API
        response = requests.get(meteo)

        # Print the response
        print(response.text)

class Footer(QWidget):
    def __init__(self):
        super().__init__()
        
        # Tworzymy layout na stopkę
        layout = QHBoxLayout()
        
        # Możesz dodać logo
        logo = QLabel()
        pixmap = QPixmap("logo.png")  # Wstaw ścieżkę do swojego logo
        logo.setPixmap(pixmap.scaled(100, 50))  # Skaluje logo do odpowiedniego rozmiaru
        
        # Dodajemy logo do layoutu
        layout.addWidget(logo)
        
        # Możesz dodać inne elementy do stopki, jeśli chcesz
        footer_text = QLabel("© 2024 DoDomuDojadę.")
        layout.addWidget(footer_text)

        # Ustawiamy layout
        self.setLayout(layout)

class Loading(QWidget):
    def __init__(self):
        super().__init__()

        # Tworzenie etykiety dla kropek
        self.loading_label = QLabel("Ładowanie danych", self)
        self.dot_label = QLabel("", self)
        self.loading_label.setAlignment(Qt.AlignCenter)
        self.dot_label.setAlignment(Qt.AlignCenter)

        # Ustawienia layoutu
        layout = QVBoxLayout()
        layout.addWidget(self.loading_label)
        layout.addWidget(self.dot_label)
        self.setLayout(layout)

        # Inicjalizacja zmiennych do animacji
        self.dot_count = 0
        self.dots = ["", ".", "..", "..."]

        self.dot_label.setStyleSheet("font-size: 48px;")

        # Ustawienie timera do animacji
        self.timer = QTimer(self)
        self.timer.timeout.connect(self.update_dots)
        self.timer.start(500)  # Czas odświeżania w milisekundach

    def update_dots(self):
        # Aktualizacja kropek w etykiecie
        self.dot_label.setText(f"{self.dots[self.dot_count]}")
        self.dot_count = (self.dot_count + 1) % len(self.dots)

class MainWindow(QWidget):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("DoDomuDojadę")
        self.setFixedSize(500, 600)
        self.setStyleSheet(app_style)
        self.layout = QVBoxLayout()

        # Inicjalizacja widgetów QLabel dla tramwajów i ogłoszeń
        self.tram_list = QLabel()
        self.announcements_list = QLabel()
        self.footer = Footer()
        self.loading_widget = Loading()
        # Zezwolenie na zawiajnie tekstu
        self.announcements_list.setWordWrap(True)

        # Dodanie widgetów do układu
        self.layout.addWidget(self.loading_widget)
        self.layout.addWidget(self.announcements_list)
        self.layout.addWidget(self.tram_list)
        self.layout.addWidget(self.footer)
        
        self.tram_list.hide()
        self.announcements_list.hide()
        self.footer.hide()

        self.setLayout(self.layout)

        # Startowanie wątku pobierającego dane
        self.data_fetcher = Tram_DataFetcher(self.update_label)
        self.data_fetcher.start()

    def update_label(self, data, announcements_data):
        # Po zakończeniu pobierania danych ukryj animację i wyświetl interfejs
        self.loading_widget.hide()
        self.tram_list.show()
        self.announcements_list.show()
        self.footer.show()

        # Update the label with the fetched data
        self.tram_list.setText("\n".join(data))
        self.announcements_list.setText("\n".join(announcements_data))

if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = MainWindow()
    window.resize(400, 300)
    window.show()
    sys.exit(app.exec())