<!DOCTYPE html>
<html lang="en" class="bg-school1">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DoDomuDojadę</title>
    <link rel="icon" type="image/x-icon" href="assets/resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link href="assets/styles/output.css" rel="stylesheet" type="text/css">
    <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  </head>
  <body>
    <div class="flex mx-1 my-2">
        <div class="flex flex-none bg-white h-20 rounded-2xl mr-1 shadow-custom">
            <div class="flex bg-white h-20 rounded-l-2xl justify-center items-center pl-3 pr-3"><img src="assets/resources/logo_samo_kolor.png" alt="logo" width="30" height="30"></div>
            <div id="date" class="flex bg-white h-20 justify-center items-center pl-3 pr-3 font-mono text-[20px] font-extrabold">
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
            <div id="time" class="flex bg-white rounded-r-2xl h-20 justify-center items-center pl-3 pr-3 font-mono text-[20px] font-extrabold">
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

                    // Aktualizacja zegara co 1 sekundę
                    setInterval(updateClock, 1000);
                    updateClock(); // Wywołanie na starcie
                </script>
            </div>
        </div>
        <div class="flex flex-auto bg-white h-20 rounded-2xl ml-1 shadow-custom justify-center items-center">
            <div id="temperature-div" class="flex bg-white h-20 justify-center items-center pl-3 pr-3 font-mono text-[20px] font-extrabold">Ładowanie...</div>
            <div id="pressure-div" class="flex bg-white h-20 justify-center items-center pl-3 pr-3 font-mono text-[20px] font-extrabold">Ładowanie...</div>
            <div id="airly-div" class="flex bg-white h-20 justify-center items-center pl-3 pr-3 font-mono text-[20px] font-extrabold">Ładowanie...</div>
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
                            $('#temperature-div').html('<p>Błąd danych pogodowych (nie można przetworzyć odpowiedzi).</p>');
                            $('#pressure-div').html('<p>Błąd danych pogodowych (nie można przetworzyć odpowiedzi).</p>');
                            $('#airly-div').html('<p>Błąd danych pogodowych (nie można przetworzyć odpowiedzi).</p>');
                            return;
                        }

                        if (response.is_active===false) {
                            $('#temperature-div').remove();
                            $('#pressure-div').remove();
                            $('#airly-div').remove();
                            return;
                        }

                        if (response.success && response.data) {
                            let data = response.data;
                            let content = `
                                <p><i class="fa-solid fa-temperature-three-quarters" style="color: #4A73AF"></i> ${data.temperature}°C</p>
                            `;
                            let content1 = `
                                <p><i class="fa-solid fa-gauge"  style="color: #4A73AF"></i> ${data.pressure} hPa</p>
                            `;
                            let content2 = `
                                <p><span style="color: #4A73AF">AQI</span>: ${data.airlyIndex}</p>
                            `;
                            $('#temperature-div').html(content);
                            $('#pressure-div').html(content1);
                            $('#airly-div').html(content2);
                        } else {
                            $('#temperature-div').html('<p>Błąd: Brak danych pogodowych</p>');
                            $('#pressure-div').html('<p>Błąd: Brak danych pogodowych</p>');
                            $('#airly-div').html('<p>Błąd: Brak danych pogodowych</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Błąd ładowania AJAX: ", error);
                        $('#temperature-div').html('<p>Błąd ładowania danych pogodowych.</p>');
                        $('#pressure-div').html('<p>Błąd ładowania danych pogodowych.</p>');
                        $('#airly-div').html('<p>Błąd ładowania danych pogodowych.</p>');
                    }
                });
            }

            setInterval(loadWeatherData, 900000)
            loadWeatherData();
        </script>
        </div>
    </div>

    <div id="countdown" class="div">
        <h2>Odliczanie</h2>
        <script>
            function loadCountdownData() {

                $.ajax({
                    url: '/display/get_countdown',
                    type: 'POST',
                    dataType: 'json',
                    data: { function: 'countdownData' },
                    success: function(response) {
                        try {
                            response = typeof response === 'string' ? JSON.parse(response) : response;
                        } catch (e) {
                            console.error("Błąd parsowania JSON:", e);
                        }

                        if (response.is_active===false) {
                            $('#countdown').remove();
                            return;
                        }


                        if (response.success && Array.isArray(response.data) && response.data.length > 0) {
                            let item = response.data[0];
                            let content = '';
                            let timestamp = parseInt(item.count_to, 10);

                            content += `<p>Title: ${item.title}</p>`;
                            content += `<p>Kontent: ${new Date(timestamp)}</p>`

                            $('#countdown').html(content);

                        } else {
                            console.error("Brak danych do wyświetlenia:", response);
                            $('#countdown').html('<p>Błąd: Brak danych</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Błąd ładowania AJAX: ", error);
                        $('#countdown').html('<p>Błąd ładowania danych kalendarza.</p>');
                    }
                });
            }

            setInterval(loadCountdownData, 60000); // 1 min
            loadCountdownData();
        </script>
    </div>

    <div class="grid grid-flow-col auto-cols-fr w-full overflow-x-auto px-1">
        <div id="left" class="bg-white rounded-2xl h-[800px] mr-1 shadow-custom">
            <div id="tram" class="bg-white rounded-2xl h-[792px] mr-1">
                <div id="tram-container" class=" px-2 py-2 ml-2 my-2">Ładowanie danych...</div>
                <script>
                    function loadTramData() {
                        $.ajax({
                            url: '/display/get_departures',
                            type: 'POST',
                            dataType: 'json',
                            data: { function: 'tramData' },
                            success: function(response) {
                                console.debug("Rozpoczęto ładowanie danych tramwajowych")
                                try {
                                    response = typeof response === 'string' ? JSON.parse(response) : response;
                                    console.debug("String został sparsowany na JSON")
                                } catch (e) {
                                    console.error("Błąd parsowania JSON:", e);
                                }

                                if (response.is_active===false) {
                                    $('#tram').remove();
                                    return;
                                }

                                if (response.success && Array.isArray(response.data)) {

                                    let content = `
                <table class="table-fixed w-full">
                    <thead>
                        <tr>
                            <th class="w-1/4"><i class="fa-solid fa-train-tram" style="color: #4A73AF"></i> Linia</th>
                            <th class="w-1/2"><i class="fa-solid fa-location-dot" style="color: #4A73AF"></i> Kierunek</th>
                            <th class="w-1/4"><i class="fa-solid fa-clock" style="color: #4A73AF"></i> Czas do odjazdu</th>
                        </tr>
                    </thead>
                    <tbody>`;
                                    let index = 0
                                    response.data.forEach(tram => {
                                        if (index < 16) {
                                            content += `
                                                    <tr class="text-[29px]">
                                                        <td class="text-center"> ${tram.line}</td>
                                                        <td class="px-4 text-center"> ${tram.direction}</td>`
                                            if (tram.minutes === 0) {
                                                content += `<td class="text-center"> odjeżdża </td>`;
                                            } else if (tram.minutes < 60) {
                                                content += `<td class="text-center"> ${tram.minutes} min</td>`;
                                            } else {
                                                let hours = Math.floor(tram.minutes / 60);
                                                let minutes = tram.minutes % 60;
                                                content += `<td class="text-center"> ${hours}h ${minutes}min</td>`;
                                            }
                                            content += `</tr>`;
                                            index += 1;
                                        }});
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

                    setInterval(loadTramData, 50000);
                    loadTramData();
                </script>
            </div>
        </div>
        <div id="right" class="bg-white rounded-2xl px-2 py-2 ml-1 shadow-custom">
            <div id="calendar" class="px-2 py-2 mx-2 my-2">
                <h2 class="mb-2"><strong>Wydarzenia</strong></h2>
                <div id="calendar-container">Ładowanie danych...</div>
                <script>
                    function loadCalendarData() {

                        $.ajax({
                            url: '/display/get_events',
                            type: 'POST',
                            dataType: 'json',
                            data: { function: 'calendarData' },
                            success: function(response) {
                                try {
                                    response = typeof response === 'string' ? JSON.parse(response) : response;
                                } catch (e) {
                                    console.error("Błąd parsowania JSON:", e);
                                }

                                if (response.is_active===false) {
                                    $('#calendar').remove();
                                    return;
                                }

                                if (response.success && Array.isArray(response.data)) {
                                    let content = '';
                                    response.data.forEach(cal => {
                                        if (cal.summary) {
                                            content += `<div class="bg-beige px-2 py-1 rounded-2xl shadow-custom text-[20px]"><p><i class='fa-regular fa-calendar' style="color: #4A73AF"></i> Wydarzenie: ${cal.summary}</p>`;
                                        } else {
                                            content += `<div class="bg-beige px-2 py-1 rounded-2xl shadow-custom text-[20px]"><p><i class='fa-regular fa-calendar' style="color: #4A73AF"></i> Wydarzenie</p>`;
                                        }
                                        content += `<p><i class='fa-solid fa-hourglass-start' style="color: #4A73AF"></i> Start: ${cal.start}</p>`;
                                        content += `<p><i class='fa-solid fa-hourglass-end' style="color: #4A73AF"></i> Koniec: ${cal.end}</p>`;
                                        if (cal.description) {
                                            content += `<p>Opis: ${cal.description}</p></div><br>`;
                                        } else {
                                            content += `</div><br>`;
                                        }
                                    });
                                    $('#calendar-container').html(content);
                                } else {
                                    console.error("Brak danych do wyświetlenia:", response);
                                    $('#calendar-container').html('<p>Błąd: Brak danych</p>');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error("Błąd ładowania AJAX: ", error);
                                $('#calendar-container').html('<p>Błąd ładowania danych kalendarza.</p>');
                            }
                        });
                    }
                    //Odświeżanie co minutę
                    setInterval(loadCalendarData, 60000); // 1 min
                    loadCalendarData();
                </script>
            </div>

            <div id="announcements" class="px-2 py-2 mx-2 my-2">
                <h2 class="mb-2"><strong>Ogłoszenia</strong></h2>
                <div id="announcements-container" class="text-[20px]">Ładowanie danych...</div>
            </div>

            <script>
                let fadeInterval;

                function displayAnnouncement(announcement) {
                    const html = `
                      <div class="announcement bg-beige rounded-2xl px-2 py-2 shadow-custom">
                        <h3>${announcement.title}</h3>
                        <p><i class="fa-solid fa-user" style="color: #4A73AF"></i> ${announcement.author}</p>
                        <p>${announcement.text}</p>
                        <p>
                          <small>
                            <i class="fa-solid fa-calendar" style="color: #4A73AF"></i> <strong>Utworzono:</strong> ${announcement.date}
                          </small> –
                          <small><strong>Ważne do:</strong> ${announcement.validUntil}</small>
                        </p>
                      </div>
                    `;
                    $('#announcements-container').html(html);
                }

                function startAnnouncementRotation(announcements) {
                    if (fadeInterval) {
                        clearInterval(fadeInterval);
                    }
                    let current = 0;
                    displayAnnouncement(announcements[current]);

                    fadeInterval = setInterval(() => {
                        $('.announcement').fadeOut(2000, function() {
                            current = (current + 1) % announcements.length;
                            displayAnnouncement(announcements[current]);
                            $('.announcement').fadeIn(2000);
                        });
                    }, 6000);
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
                                $('#announcements').remove();
                                return;
                            }

                            if (response.success && Array.isArray(response.data)) {
                                if (response.data.length === 0) {
                                    $('#announcements-container').html('<p>Brak ważnych ogłoszeń.</p>');
                                } else {
                                    startAnnouncementRotation(response.data);
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

                $(document).ready(function() {
                    loadAnnouncements();
                    setInterval(loadAnnouncements, 60000);
                });
            </script>

        </div>
    </div>
  </body>
</html>

