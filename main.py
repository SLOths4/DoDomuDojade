from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
from PySide6.QtWidgets import QApplication, QWidget, QVBoxLayout, QLabel, QHBoxLayout, QTableWidget, QTableWidgetItem
from PySide6.QtGui import QPixmap
from PySide6.QtCore import QTimer, Qt
import time
import sys
import requests
import json
from threading import Thread

with open('data.json', 'r') as jsonfile:
    data = json.load(jsonfile)
    awf73 = data["API"][1]["url"]
    meteo = data["API"][0]["url"]
    app_style = data["stylesheet"][0]["style"]

print(awf73)
print(meteo)

class Tram_DataFetcher(Thread):
    def __init__(self, update_callback):
        super().__init__()
        self.update_callback = update_callback
        self.daemon = True

    def run(self):
        options = webdriver.ChromeOptions()
        options.add_argument("--headless")
        driver = webdriver.Chrome(options=options, service=Service(ChromeDriverManager().install()))

        while True:
            try:
                driver.get(awf73)

                # Czekanie na załadowanie elementów
                wait = WebDriverWait(driver, 10)  # Czeka do 10 sekund
                tramwaje = wait.until(EC.presence_of_all_elements_located((By.CLASS_NAME, "line")))
                odjazdy = wait.until(EC.presence_of_all_elements_located((By.CLASS_NAME, "time")))
                kierunki = wait.until(EC.presence_of_all_elements_located((By.CLASS_NAME, "direction")))
                
                announcements_data = []
                tram_data = []

                try:
                    informacje_a = wait.until(EC.presence_of_element_located((By.ID, "messages")))
                    informacje = informacje_a.find_element(By.TAG_NAME, "a")
                    announcements_data.append(informacje.text)
                except Exception as e:
                    print("Nie znaleziono elementu z ogłoszeniami:", e)

                for tramwaj, odjazd, kierunek in zip(tramwaje, odjazdy, kierunki):
                    tram_data.append((tramwaj.text, odjazd.text, kierunek.text))

                self.update_callback(tram_data, announcements_data)

            except Exception as e:
                print("Błąd przy pobieraniu danych:", e)

            time.sleep(1)

        driver.quit()

class Meteo_DataFetcher(Thread):
    def __init__(self, update_callback):
        super().__init__()
        self.update_callback = update_callback
        self.daemon = True

    def run(self):
        while True:
            try:
                response = requests.get(meteo)
                if response.status_code == 200:
                    data = response.json()
                    self.update_callback(data)
                else:
                    print("Błąd przy pobieraniu danych pogodowych:", response.status_code)
            except Exception as e:
                print("Błąd przy połączeniu z API pogodowym:", e)

            time.sleep(60)  # Odświeżanie co minutę

class Footer(QWidget):
    def __init__(self):
        super().__init__()
        layout = QHBoxLayout()
        logo = QLabel()
        pixmap = QPixmap("logo.png")
        logo.setPixmap(pixmap.scaled(100, 50))
        layout.addWidget(logo)
        footer_text = QLabel("© 2024 DoDomuDojadę.")
        layout.addWidget(footer_text)
        self.setLayout(layout)

class Loading(QWidget):
    def __init__(self):
        super().__init__()
        self.loading_label = QLabel("Ładowanie danych", self)
        self.dot_label = QLabel("", self)
        self.loading_label.setAlignment(Qt.AlignCenter)
        self.dot_label.setAlignment(Qt.AlignCenter)
        layout = QVBoxLayout()
        layout.addWidget(self.loading_label)
        layout.addWidget(self.dot_label)
        self.setLayout(layout)
        self.dot_count = 0
        self.dots = ["", ".", "..", "..."]
        self.dot_label.setStyleSheet("font-size: 48px;")
        self.timer = QTimer(self)
        self.timer.timeout.connect(self.update_dots)
        self.timer.start(500)

    def update_dots(self):
        self.dot_label.setText(f"{self.dots[self.dot_count]}")
        self.dot_count = (self.dot_count + 1) % len(self.dots)

class MainWindow(QWidget):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("DoDomuDojadę")
        self.setFixedSize(500, 600)
        self.setStyleSheet(app_style)
        self.layout = QVBoxLayout()
        self.tram_table = QTableWidget()
        self.announcements_list = QLabel()
        self.footer = Footer()
        self.loading_widget = Loading()
        
        self.tram_table.setColumnCount(3)
        self.tram_table.horizontalHeader().setVisible(False)
        self.tram_table.verticalHeader().setVisible(False)

        
        self.layout.addWidget(self.loading_widget)
        self.layout.addWidget(self.announcements_list)
        self.layout.addWidget(self.tram_table)
        self.layout.addWidget(self.footer)
        self.tram_table.hide()
        self.announcements_list.hide()
        self.footer.hide()
        self.setLayout(self.layout)
        self.data_fetcher = Tram_DataFetcher(self.update_table)
        self.data_fetcher.start()

    def update_table(self, tram_data, announcements_data):
        self.loading_widget.hide()
        self.tram_table.show()
        self.footer.show()

        # Sprawdzenie, czy tabela ma już jakieś dane
        current_row_count = self.tram_table.rowCount()

        # Zaktualizuj lub dodaj wiersze
        for row, (tramwaj, odjazd, kierunek) in enumerate(tram_data):
            if row < current_row_count:
                self.tram_table.item(row, 0).setText(tramwaj)
                self.tram_table.item(row, 1).setText(odjazd)
                self.tram_table.item(row, 2).setText(kierunek)
            else:
                # Dodaj nowy wiersz, jeśli nie ma wystarczającej ilości wierszy
                self.tram_table.insertRow(row)
                self.tram_table.setItem(row, 0, QTableWidgetItem(tramwaj))
                self.tram_table.setItem(row, 1, QTableWidgetItem(odjazd))
                self.tram_table.setItem(row, 2, QTableWidgetItem(kierunek))

        if announcements_data:
            self.announcements_list.setText("\n".join(announcements_data))
        else:
            self.announcements_list.setText("Brak dostępnych ogłoszeń.")
        self.announcements_list.show()

if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = MainWindow()
    window.resize(400, 300)
    window.show()
    sys.exit(app.exec())