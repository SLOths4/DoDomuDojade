<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>DoDomuDojadę</title>
    <link rel="icon" type="image/x-icon" href="resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link href="styles/style.css" rel="stylesheet" type="text/css">
      <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
  </head>
  <body>
    <?php
    require_once __DIR__ . '/../vendor/autoload.php';

    use Monolog\Handler\StreamHandler;
    use Monolog\Level;
    use Monolog\Logger;
    use src\utilities\WeatherService;

    $config = require './config.php';
    $db_host = $config['Database']['db_host'];

    $logger = new Logger('AppHandler');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/log/app.log', Level::Debug));

    $pdo = new PDO($db_host);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    ?>
    <!-- IMPORT HEADER -->
    <?php include('./functions/header.php'); ?>

    <!-- IMPORT WEATHER MODULE -->
    <div id="weather" class="div">
        <h2>Dzisiejsza pogoda</h2>
        <?php
        include('./utilities/WeatherService.php');


        $weatherService = new WeatherService();
        try {
        $weatherServiceResponse = $weatherService->Weather();

        echo "<i class='fa-solid fa-temperature-full'></i>  " . htmlspecialchars($weatherServiceResponse['imgw_temperature']) . "&deg;C" . "<br>";
        echo "<i class='fa-solid fa-gauge'></i>  ". htmlspecialchars($weatherServiceResponse['imgw_pressure']) . " hPa". "<br>";
        echo "<i class='fa-solid fa-lungs'></i>  ". htmlspecialchars($weatherServiceResponse['airly_index_value']). "<br>";
        } catch (Exception $e) {
            echo "No weather today :-)";
        }
        ?>
    </div>

    <div id="tram" class="div">
        <h2>Odjazdy tramwajów z przystanku AWF 73</h2>
        <?php
        include('./utilities/TramService.php');
        use src\utilities\TramService;

        $tram_service = new TramService($logger, "https://www.peka.poznan.pl/vm/method.vm");
        try {
            $tram_service_departures = $tram_service->getTimes("AWF73");

            if (isset($tram_service_departures['success']['times']) && is_array($tram_service_departures['success']['times'])) {
                echo "<h2>Odjazdy tramwajów:</h2>";
                foreach ($tram_service_departures['success']['times'] as $departure) {
                    echo "<div class='tram-line'>";
                    echo "<i class='fa-solid fa-train'></i>  " . htmlspecialchars($departure['line']) . "<br>";
                    echo "<i class='fa-solid fa-clock'></i>  " . htmlspecialchars($departure['minutes']) . " minut<br>";
                    echo "<i class='fa-solid fa-location-arrow'></i> " . htmlspecialchars($departure['direction']) . "<br>";
                    echo "</div>";
                }
            } else {
                echo "Brak danych o odjazdach.";
            }
        } catch (Exception $e) {
            echo "No departures today :-)";
        }
        ?>
    </div>

    <div id="announcements" class="div">
        <h2>Ogłoszenia</h2>
        <?php
        include('./utilities/AnnouncementService.php');
        include('./utilities/UserService.php');
        use src\utilities\AnnouncementService;
        use src\utilities\UserService;

        try {
            $announcement_service = new AnnouncementService($logger, $pdo);
            $user_service = new UserService($logger, $pdo);
            $announcement_service_announcements = $announcement_service->getValidAnnouncements();
            foreach ($announcement_service_announcements as $announcement) {


                try {
                    $user = $user_service->getUserById($announcement['user_id']);
                    $author_username = $user['username'] ?? 'Nieznany użytkownik';
                } catch (Exception $e) {
                    $author_username = $announcement['user_id'] ?? 'Nieznany autor';
                }
                echo "<div class='announcement'>";
                echo "<h3>" . htmlspecialchars($announcement['title']) . "</h3><br>";
                echo htmlspecialchars($author_username) . " | " . htmlspecialchars($announcement['date']) . "<br>";
                echo "Ważne do: " . htmlspecialchars($announcement['valid_until']) . "<br>";
                echo htmlspecialchars($announcement['text']) . "<br><br>";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "No announcements available.";
        }
        ?>
    </div>

    <script>
        function loadWeather() {
            fetch('WeatherServiceAjax.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const weatherDiv = document.getElementById('weather');
                        weatherDiv.innerHTML = `
                        <h2>Dzisiejsza pogoda</h2>
                        <i class="fa-solid fa-temperature-full"></i> ${data.data.temperature}&deg;C<br>
                        <i class="fa-solid fa-gauge"></i> ${data.data.pressure} hPa<br>
                        <i class="fa-solid fa-lungs"></i> ${data.data.airlyIndex}<br>
                    `;
                    } else {
                        console.error('Weather data fetch failed:', data.message);
                    }
                })
                .catch(error => console.error('Error fetching weather data:', error));
        }

        // Ładowanie pogody po kliknięciu przycisku lub automatycznie
        document.addEventListener('DOMContentLoaded', () => {
            loadWeather(); // Automatyczne załadowanie pogody po załadowaniu strony
        });
    </script>
    <div id="weather" class="div">
        <h2>Dzisiejsza pogoda</h2>
        <button onclick="loadWeather()">Odśwież pogodę</button>
    </div>
    <script>
        function loadAnnouncements() {
            fetch('AnnouncementsServiceAjax.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const announcementsDiv = document.getElementById('announcements');
                        let html = "<h2>Ogłoszenia</h2>";
                        data.data.forEach(announcement => {
                            html += `
                            <div class="announcement">
                                <h3>${announcement.title}</h3>
                                <p><strong>${announcement.author}</strong> | ${announcement.date}</p>
                                <p>Ważne do: ${announcement.validUntil}</p>
                                <p>${announcement.text}</p>
                            </div>
                        `;
                        });
                        announcementsDiv.innerHTML = html;
                    } else {
                        console.error('Failed to fetch announcements:', data.message);
                    }
                })
                .catch(error => console.error('Error fetching announcements:', error));
        }

        // Ładowanie ogłoszeń po kliknięciu przycisku lub automatycznie
        document.addEventListener('DOMContentLoaded', () => {
            loadAnnouncements(); // Automatyczne załadowanie ogłoszeń po załadowaniu strony
        });
    </script>
    <div id="announcements" class="div">
        <h2>Ogłoszenia</h2>
        <button onclick="loadAnnouncements()">Odśwież ogłoszenia</button>
    </div>
    <script>
        function loadTrams(stopId = 'AWF73') {
            // Tworzenie żądania AJAX do endpointu tram_ajax.php
            fetch(`TramServiceAjax.php?stop=${stopId}`)
                .then(response => response.json())
                .then(data => {
                    const tramDiv = document.getElementById('tram');
                    if (data.success) {
                        // Generowanie treści HTML na podstawie danych
                        let html = `<h2>Odjazdy tramwajów z przystanku ${stopId}</h2>`;
                        if (data.data.length > 0) {
                            data.data.forEach(tram => {
                                html += `
                                <div class="tram-line">
                                    <i class="fa-solid fa-train"></i> Linia: ${tram.line}<br>
                                    <i class="fa-solid fa-clock"></i> Odjazd za: ${tram.minutes} minut<br>
                                    <i class="fa-solid fa-location-arrow"></i> Kierunek: ${tram.direction}<br>
                                </div>
                            `;
                            });
                        } else {
                            html += '<p>Brak danych o nadchodzących tramwajach.</p>';
                        }
                        tramDiv.innerHTML = html;
                    } else {
                        tramDiv.innerHTML = `<h2>Błąd ładowania tramwajów</h2><p>${data.message}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching tram data:', error);
                    document.getElementById('tram').innerHTML = "<h2>Błąd!</h2><p>Nie udało się załadować danych tramwajowych.</p>";
                });
        }

        // Automatyczne ładowanie przy załadowaniu strony
        document.addEventListener('DOMContentLoaded', () => {
            loadTrams(); // Domyślnie ładuje przystanek "AWF73"
        });
    </script>
    <div id="tram" class="div">
        <h2>Odjazdy tramwajów z przystanku:</h2>
        <select id="tramStop" onchange="loadTrams(this.value)">
            <option value="AWF73" selected>AWF 73</option>
            <option value="RondoKaponiera">Rondo Kaponiera</option>
            <option value="Pestka">Pestka</option>
        </select>
        <button onclick="loadTrams(document.getElementById('tramStop').value)">Odśwież odjazdy</button>
    </div>
    <!-- IMPORT FOOTER -->
    <?php include('functions/footer.php'); ?>
  </body>
</html>

