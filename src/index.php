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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  </head>
  <body>
    <?php
    require_once __DIR__ . '/../vendor/autoload.php';

    ?>

    <div class="div"><img src="resources/logo_samo_kolor.png" alt="logo" width="30" height="30"></div>

    <div id="time" class="div">
        <script>
            function updateClock() {
                const timeElement = document.getElementById('time');
                if (!timeElement) return;

                const now = new Date();
                const hours = now.getHours().toString().padStart(2, '0');
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const seconds = now.getSeconds().toString().padStart(2, '0');

                timeElement.innerHTML = `<h2><i class="fa-solid fa-clock"></i> ${hours}:${minutes}:${seconds}</h2>`;
            }

            // Aktualizacja zegara co 1 sekundę
            setInterval(updateClock, 1000);
            updateClock(); // Wywołanie na starcie
        </script>
    </div>
    <div id="date" class="div">
        <script>
            function updateDate() {
                const dateElement = document.getElementById('date');
                if (!dateElement) return;

                const now = new Date();
                const day = now.getDate().toString().padStart(2, '0');
                const month = (now.getMonth() + 1).toString().padStart(2, '0');
                const year = now.getFullYear();

                dateElement.innerHTML = `<h2><i class="fa-solid fa-calendar"></i> ${day}.${month}.${year}</h2>`;
            }

            setInterval(updateDate, 1000);
            updateDate();
        </script>
    </div>

    <!-- IMPORT WEATHER MODULE -->
    <div id="weather" class="div">
        <h2>Dzisiejsza pogoda</h2>
        <div id="weather-container">Ładowanie danych...</div>
        <script>
            function loadWeatherData() {
                $.ajax({
                    url: 'refresh.php',
                    type: 'GET',
                    dataType: 'json',
                    data: { function: 'weatherData' },
                    success: function(response) {
                        try {
                            // Jeśli odpowiedź zwrócona jako string, parsujemy na JSON
                            response = typeof response === 'string' ? JSON.parse(response) : response;
                        } catch (e) {
                            console.error("Błąd parsowania JSON:", e);
                            $('#weather-container').html('<p>Błąd danych pogodowych (nie można przetworzyć odpowiedzi).</p>');
                            return;
                        }
                        console.log(response); // Debugging

                        // Sprawdzamy, czy odpowiedź jest poprawna i czy zawiera dane
                        if (response.success && response.data) {
                            let data = response.data; // Dane pogodowe
                            let content = `
                                <p><i class="fa-solid fa-temperature-three-quarters"></i> ${data.temperature}°C</p>
                                <p><i class="fa-solid fa-gauge"></i> ${data.pressure} hPa</p>
                                <p>Indeks jakości powietrza (Airly): ${data.airlyIndex}</p>
                            `;
                            $('#weather-container').html(content);
                        } else {
                            $('#weather-container').html('<p>Błąd: Brak danych pogodowych</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Błąd ładowania AJAX: ", error);
                        $('#weather-container').html('<p>Błąd ładowania danych pogodowych.</p>');
                    }
                });
            }

            setInterval(loadWeatherData, 900000)
            loadWeatherData();
        </script>
    </div>

    <div id="tram" class="div">
        <h2>Odjazdy</h2>
        <div id="tram-container">Ładowanie danych...</div>
        <script>
            function loadTramData() {
                $.ajax({
                    url: 'refresh.php',
                    type: 'GET',
                    dataType: 'json',
                    data: { function: 'tramData' },
                    success: function(response) {
                        try {
                            response = typeof response === 'string' ? JSON.parse(response) : response;
                        } catch (e) {
                            console.error("Błąd parsowania JSON:", e);
                        }
                        if (response.success && Array.isArray(response.data)) {
                            let content = `
        <table>
            <thead>
                <tr>
                    <th>Linia</th>
                    <th>Kierunek</th>
                    <th>Czas do odjazdu</th>
                </tr>
            </thead>
            <tbody>`;
                            response.data.forEach(tram => {
                                content += `
            <tr>
                <td><i class="fa-solid fa-train-tram"></i> ${tram.line}</td>
                <td><i class="fa-solid fa-location-dot"></i> ${tram.direction}</td>
                <td><i class="fa-solid fa-clock"></i> ${tram.minutes} min</td>
            </tr>`;
                            });
                            content += `
            </tbody>
        </table>`;
                            $('#tram-container').html(content);
                        } else {
                            console.error("Brak danych do wyświetlenia:", response);
                            $('#tram-container').html('<p>Błąd: Brak danych</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Błąd ładowania AJAX: ", error);
                        $('#tram-container').html('<p>Błąd ładowania danych tramwajowych.</p>');
                    }
                });
            }

            //Odświeżanie co 5 sekund
            setInterval(loadTramData, 5000);
            loadTramData();
        </script>
    </div>

    <div id="announcements" class="div">
        <h2>Ogłoszenia</h2>
        <div id="announcements-container">Ładowanie danych...</div>
        <script>
            function loadAnnouncements() {
                $.ajax({
                    url: 'refresh.php',
                    type: 'GET',
                    dataType: 'json',
                    data: { function: 'announcementsData' },
                    success: function(response) {
                        try {
                            response = typeof response === 'string' ? JSON.parse(response) : response;
                        } catch (e) {
                            console.error("Błąd parsowania JSON:", e);
                        }

                        if (response.success && Array.isArray(response.data)) {
                            // Sprawdzenie, czy lista danych jest pusta
                            if (response.data.length === 0) {
                                $('#announcements-container').html('<p>Brak ważnych ogłoszeń.</p>');
                            } else {
                                let content = '<div class="announcements-list">';
                                response.data.forEach(announcement => {
                                    content += `
                                <div class="announcement-item">
                                    <h3>${announcement.title}</h3>
                                    <p><i class="fa-solid fa-user"></i> ${announcement.author}</p>
                                    <p>${announcement.text}</p>
                                    <p><small><i class="fa-solid fa-calendar"></i> ${announcement.date}</small> – <small><strong>Ważne do:</strong> ${announcement.validUntil}</small></p>
                                </div>
                            `;
                                });
                                content += '</div>';
                                $('#announcements-container').html(content);
                            }
                        } else {
                            console.error("Brak danych lub błąd odpowiedzi:", response);
                            $('#announcements-container').html('<p>Błąd: Brak danych ogłoszeń.</p>');
                        }
                    },
                    error: function() {
                        console.error("Błąd ładowania danych AJAX.");
                        $('#announcements-container').html('<p>Błąd ładowania danych ogłoszeń.</p>');
                    }
                });
            }

            // Odświeżanie danych co minutę
            setInterval(loadAnnouncements, 60000);
            loadAnnouncements();
        </script>

    </div>

    <!-- IMPORT FOOTER -->
    <?php include('functions/footer.php'); ?>
  </body>
</html>

