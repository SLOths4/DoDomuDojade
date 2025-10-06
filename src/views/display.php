<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DoDomuDojadę</title>
    <link rel="icon" type="image/x-icon" href="/assets/resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="/assets/styles/dist/output.css" rel="stylesheet" type="text/css">
</head>
<body class="bg-primary-200">
<div class="flex mx-1 my-2">
    <div class="flex flex-auto bg-white h-20 rounded-2xl mr-1 shadow-custom overflow-hidden justify-around items-center">
        <div class="flex h-full justify-center items-center pl-2 pr-2 font-mono text-xl font-extrabold"><img src="assets/resources/logo_samo_kolor.png" alt="logo" width="40" height="40"></div>
        <div id="date" class="flex h-full justify-center items-center pl-2 pr-2 font-mono text-sm md:max-lg:text-lg lg:text-2xl font-extrabold">
            <script>
                function updateDate() {
                    const dateElement = document.getElementById('date');
                    if (!dateElement) return;

                    const now = new Date();
                    const day = now.getDate().toString().padStart(2, '0');
                    const month = (now.getMonth() + 1).toString().padStart(2, '0');
                    const year = now.getFullYear();

                    dateElement.innerHTML = `<h2><i class="fa-solid fa-calendar" style="color: #4A73AF"></i> ${day}.${month}.${year}</h2>`;
                }

                setInterval(updateDate, 1000);
                updateDate();
            </script>
        </div>
        <div id="time" class="flex h-full justify-center items-center pl-2 pr-2 font-mono text-sm md:max-lg:text-lg lg:text-2xl font-extrabold">
            <script>
                function updateClock() {
                    const timeElement = document.getElementById('time');
                    if (!timeElement) return;

                    const now = new Date();
                    const hours = now.getHours().toString().padStart(2, '0');
                    const minutes = now.getMinutes().toString().padStart(2, '0');
                    const seconds = now.getSeconds().toString().padStart(2, '0');

                    timeElement.innerHTML = `<h2><i class="fa-solid fa-clock" style="color: #4A73AF"></i> ${hours}:${minutes}:${seconds}</h2>`;
                }

                setInterval(updateClock, 1000);
                updateClock();
            </script>
        </div>
    </div>

    <div class="flex flex-auto bg-white h-20 rounded-2xl ml-1 shadow-custom overflow-hidden justify-around items-center">
        <div id="temperature" class="flex h-full justify-center items-center pl-2 pr-2 font-mono text-xl font-extrabold">Ładowanie...</div>
        <div id="pressure" class="flex h-full justify-center items-center pl-2 pr-2 font-mono text-xl font-extrabold">Ładowanie...</div>
        <div id="airly" class="flex h-full justify-center items-center pl-2 pr-2 font-mono text-xl font-extrabold">Ładowanie...</div>
        <script>
            function loadWeatherData() {
                $.ajax({
                    url: '/display/get_weather',
                    type: 'POST',
                    dataType: 'json',
                    data: { function: 'weatherData' },
                    success: function(response) {
                        try {
                            response = typeof response === 'string' ? JSON.parse(response) : response;
                        } catch (e) {
                            console.error("Błąd parsowania JSON:", e);
                            $('#temperature').html('<p>Błąd danych pogodowych (nie można przetworzyć odpowiedzi).</p>');
                            $('#pressure').html('<p>Błąd danych pogodowych (nie można przetworzyć odpowiedzi).</p>');
                            $('#airly').html('<p>Błąd danych pogodowych (nie można przetworzyć odpowiedzi).</p>');
                            return;
                        }

                        if (response.is_active===false) {
                            $('#temperature').addClass('hidden');
                            $('#pressure').addClass('hidden');
                            $('#airly').addClass('hidden');
                            return;
                        }

                        $('#temperature').removeClass('hidden');
                        $('#pressure').removeClass('hidden');
                        $('#airly').removeClass('hidden');

                        if (response.success && response.data) {
                            let data = response.data;
                            let colors = '';

                            if (data.temperature <= -10) {
                                colors = '#AECBFA';
                            } else if (data.temperature <= 0 && data.temperature > -10) {
                                colors = '#A0F0ED';
                            } else if (data.temperature > 0 && data.temperature <= 5) {
                                colors = '#000000';
                            } else if (data.temperature > 5 && data.temperature <= 15) {
                                colors = '#FFF9A6';
                            } else if (data.temperature > 15 && data.temperature <= 25) {
                                colors = '#FFD1A4';
                            } else if (data.temperature > 25) {
                                colors = '#FFB3B3';
                            }
                            let content = `
                                        <p><i class="fa-solid fa-temperature-three-quarters" style="color: ${colors}"></i> ${data.temperature}°C</p>
                                    `;
                            let content1 = `
                                        <p><i class="fa-solid fa-gauge"  style="color: #4A73AF"></i> ${data.pressure} hPa</p>
                                    `;
                            let content2 = `
                                        <p><i class="fas fa-air-freshener" style="color: ${data.airlyColour}"></i> ${data.airlyAdvice}</p>
                                    `;
                            $('#temperature').html(content);
                            $('#pressure').html(content1);
                            $('#airly').html(content2);
                        } else {
                            $('#temperature').html('<p>Błąd: Brak danych pogodowych</p>');
                            $('#pressure').html('<p>Błąd: Brak danych pogodowych</p>');
                            $('#airly').html('<p>Błąd: Brak danych pogodowych</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Błąd ładowania AJAX: ", error);
                        $('#temperature').html('<p>Błąd ładowania danych pogodowych.</p>');
                        $('#pressure').html('<p>Błąd ładowania danych pogodowych.</p>');
                        $('#airly').html('<p>Błąd ładowania danych pogodowych.</p>');
                    }
                });
            }

            setInterval(loadWeatherData, 900000)
            loadWeatherData();
        </script>
    </div>
</div>

<div class="grid grid-flow-col auto-cols-fr w-full overflow-x-auto px-1">
    <div id="left" class="bg-white rounded-2xl h-full shadow-custom py-1">

        <div id="tram" class="bg-white rounded-2xl h-full">

            <div id="tram-container" class="h-full"><p class="text-[20px] m-2 p-2">Ładowanie danych...</p></div>

            <script>
                const MAX_ROWS = 25;
                const ENDPOINT = '/display/get_departures';

                function formatMinutes(min) {
                    if (min === 0) return '&lt;1';
                    if (min < 60) return String(min);
                    const hours = Math.floor(min / 60);
                    const minutes = min % 60;
                    return `${hours}h ${minutes}`;
                }

                function buildHeader() {
                    return `
    <table class="table-fixed w-full">
      <thead>
        <tr>
          <th class="w-1/6 text-xs md:max-lg:text-base lg:text-lg"><i class="fa-solid fa-train-tram" style="color: #4A73AF"></i> Linia</th>
          <th class="w-4/6 text-xs md:max-lg:text-base lg:text-lg"><i class="fa-solid fa-location-dot" style="color: #4A73AF"></i> Kierunek</th>
          <th class="w-1/6 text-xs md:max-lg:text-base lg:text-lg"><i class="fa-solid fa-clock" style="color: #4A73AF"></i><a class="max-sm:hidden"> Odjazd (min)</a></th>
        </tr>
      </thead>
      <tbody>
    `;
                }

                function buildRow(tram) {
                    const minutesCell = formatMinutes(tram.minutes);
                    const rowClass = 'text-[25px] text-xs md:max-lg:text-base lg:text-lg';
                    return `
      <tr class="${rowClass}">
        <td class="text-center w-1/6">${tram.line}</td>
        <td class="text-center w-4/6">${tram.direction}</td>
        <td class="text-center w-1/6">${minutesCell}</td>
      </tr>
    `;
                }

                function safeParseResponse(resp) {
                    try {
                        return (typeof resp === 'string') ? JSON.parse(resp) : resp;
                    } catch (e) {
                        console.error("Błąd parsowania JSON:", e);
                        return null;
                    }
                }

                function showError(messageShort) {
                    $('#tram-container').html(
                        `<div class="bg-amber-100 border border-yellow-500 rounded-lg flex items-center space-x-2">
            <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i>
            <p class="text-yellow-500 text-sm font-medium">${messageShort}</p>
         </div>`
                    );
                }

                function loadTramData() {
                    $.ajax({
                        url: ENDPOINT,
                        type: 'POST',
                        dataType: 'json',
                        data: { function: 'tramData' },
                        success: function(raw) {
                            const response = safeParseResponse(raw);
                            if (!response) {
                                showError('Błąd: Nieprawidłowa odpowiedź JSON.');
                                return;
                            }

                            if (response.is_active === false) {
                                $('#tram').addClass('hidden');
                                return;
                            }

                            $('#tram').removeClass('hidden');

                            if (!(response.success && Array.isArray(response.data))) {
                                console.error("Brak danych do wyświetlenia:", response);
                                showError('Błąd: Brak danych');
                                return;
                            }

                            const data = response.data;
                            if (data.length === 0) {
                                showError('Błąd: Brak kursów.');
                                return;
                            }

                            // Pełne renderowanie wszystkich dostępnych kursów (do MAX_ROWS) przy każdym odświeżeniu
                            let maxIndex = Math.min(data.length, MAX_ROWS);
                            let html = buildHeader();
                            for (let i = 0; i < maxIndex; i++) {
                                html += buildRow(data[i]);
                            }
                            html += `</tbody></table>`;
                            $('#tram-container').html(html);
                        },
                        error: function(xhr, status, error) {
                            console.error("Błąd ładowania AJAX: ", error);
                            showError('Błąd ładowania danych tramwajowych.');
                        }
                    });
                }

                setInterval(loadTramData, 50000);
                loadTramData();

            </script>
        </div>
    </div>

    <div id="middle" class="bg-white rounded-2xl h-[800px] ml-2 shadow-custom py-1">
        <div id="countdown" class="flex justify-center items-center bg-beige rounded-2xl m-2 p-2 shadow-custom font-mono text-xl font-extrabold">Ładowanie...</div>

        <script>
            function loadCountdownData() {

                $.ajax({
                    url: '/display/get_countdown',
                    type: 'POST',
                    dataType: 'json',
                    data: {function: 'countdownData'},
                    success: function (response) {
                        try {
                            response = typeof response === 'string' ? JSON.parse(response) : response;
                        } catch (e) {
                            console.error("Błąd parsowania JSON:", e);
                        }

                        if (response.is_active === false) {
                            $('#countdown').addClass('hidden');
                            return;
                        }

                        $('#countdown').removeClass('hidden');

                        if (response.success && Array.isArray(response.data) && response.data.length > 0) {

                            let item = response.data[0];
                            let content = '';
                            let timestamp = new Date(item.count_to * 1000);

                            function countdown() {
                                let now = new Date().getTime();
                                let distance = timestamp - now;

                                let days = Math.floor(distance / (1000 * 60 * 60 * 24));
                                let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                let seconds = Math.floor((distance % (1000 * 60)) / 1000);

                                content = `<p>Do ${item.title} zostało `;
                                if (days > 0) {
                                    content += `${days} dni `;
                                }
                                if (hours > 0) {
                                    content += `${hours} godzin `;
                                }
                                if (minutes > 0) {
                                    content += `${minutes} minut `;
                                }
                                content += `${seconds} sekund.</p>`;

                                $('#countdown').html(content);
                            }

                            setInterval(countdown, 1000);

                        } else {
                            console.error("Brak danych do wyświetlenia:", response);
                            $('#countdown').html('<div class="bg-amber-100 border border-yellow-500 rounded-lg flex items-center space-x-2"> <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i><p class="text-yellow-500 text-sm font-medium p-2 ">Brak aktualnego odliczania</p></div>');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Błąd ładowania AJAX: ", error);
                        $('#countdown').html('<div class="bg-amber-100 border border-yellow-500 rounded-lg flex items-center space-x-2"> <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i><p class="text-yellow-500 text-sm font-medium">Błąd ładowania danych odliczania</p></div>');
                    }
                });
            }

            setInterval(loadCountdownData, 60000);
            loadCountdownData();
        </script>

        <div id="announcements" class="py-2 mx-2 my-2">
            <div id="announcements-container" class="text-[20px]">Ładowanie danych...</div>
        </div>

        <script>
            let fadeInterval;

            function displayAnnouncements(announcementsChunk) {
                const html = announcementsChunk.map(announcement => `
                            <div class="announcement bg-beige rounded-2xl px-2 py-2 shadow-custom mb-4 w-full overflow-x-hidden break-words whitespace-normal">
                            <span class="flex items-baseline font-bold">
                                <h3>${announcement.title}</h3>
                            </span>
                                <p class="text-[18px]">${announcement.text}</p>
                                <p class="text-[15px]">
                                  <small>
                                    <i class="fa-solid fa-calendar" style="color: #4A73AF"></i> <strong>Utworzono:</strong> ${announcement.date}
                                  </small>
                                  <small>
                                        <strong>Ważne do:</strong> ${announcement.validUntil}
                                    </small>
                                  <i class="fa-solid fa-user pl-2" style="color: #4A73AF"></i> ${announcement.author}
                                </p>
                            </div>
                        `).join('');

                $('#announcements-container').html(html);
            }

            function chunkArray(array, chunkSize) {
                const chunks = [];
                for (let i = 0; i < array.length; i += chunkSize) {
                    chunks.push(array.slice(i, i + chunkSize));
                }
                return chunks;
            }

            function startAnnouncementRotation(announcements) {
                if (fadeInterval) {
                    clearInterval(fadeInterval);
                }

                const announcementGroups = chunkArray(announcements, 4);

                if (announcementGroups.length === 1) {
                    displayAnnouncements(announcementGroups[0]);
                    return;
                }

                let currentGroup = 0;

                displayAnnouncements(announcementGroups[currentGroup]);

                fadeInterval = setInterval(() => {
                    $('#announcements-container').fadeOut(1000, function () {
                        currentGroup = (currentGroup + 1) % announcementGroups.length;
                        displayAnnouncements(announcementGroups[currentGroup]);
                        $('#announcements-container').fadeIn(1000);
                    });
                }, 8000);
            }

            function loadAnnouncements() {
                $.ajax({
                    url: '/display/get_announcements',
                    type: 'POST',
                    dataType: 'json',
                    data: { function: 'announcementsData' },
                    success: function(response) {
                        if (typeof response === 'string') {
                            try {
                                response = JSON.parse(response);
                            } catch (e) {
                                console.error("Błąd parsowania JSON:", e);
                                $('#announcements-container').html('<p>Błąd ładowania danych ogłoszeń.</p>');
                                return;
                            }
                        }

                        if (response.is_active === false) {
                            $('#announcements').addClass('hidden');
                            return;
                        }

                        $('#announcements').removeClass('hidden');

                        if (response.success && Array.isArray(response.data)) {
                            if (response.data.length === 0) {
                                $('#announcements-container').html('<div class="bg-amber-100 border border-yellow-500 rounded-lg flex items-center space-x-2"> <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i><p class="text-yellow-500 text-sm font-medium">Brak ważnych ogłoszeń</p></div>');
                            } else {
                                startAnnouncementRotation(response.data);
                            }
                        } else {
                            console.error("Brak danych lub błąd odpowiedzi:", response);
                            $('#announcements-container').html('<div class="bg-amber-100 border border-yellow-500 rounded-lg flex items-center space-x-2"> <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i><p class="text-yellow-500 text-sm font-medium">Błąd: Brak danych ogłoszeń</p></div>');
                        }
                    },
                    error: function() {
                        console.error("Błąd ładowania danych AJAX.");
                        $('#announcements-container').html('<div class="bg-amber-100 border border-yellow-500 rounded-lg flex items-center space-x-2"> <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i><p class="text-yellow-500 text-sm font-medium">Błąd ładowania danych ogłoszeń</p></div>');
                    }
                });
            }

            $(document).ready(function() {
                loadAnnouncements();
                setInterval(loadAnnouncements, 60000);
            });
        </script>
    </div>
</div>
</body>
</html>
