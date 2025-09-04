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
    <body class="bg-school1">
        <div class="flex mx-1 my-2">
            <div class="flex flex-auto bg-white h-20 rounded-2xl mr-1 shadow-custom overflow-hidden justify-around items-center">
                <div class="flex h-full justify-center items-center pl-2 pr-2 font-mono text-xl font-extrabold"><img src="assets/resources/logo_samo_kolor.png" alt="logo" width="40" height="40"></div>
                <div id="date" class="flex h-full justify-center items-center pl-2 pr-2 font-mono text-xl font-extrabold">
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
                <div id="time" class="flex h-full justify-center items-center pl-2 pr-2 font-mono text-xl font-extrabold">
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
                                    $('#temperature').remove();
                                    $('#pressure').remove();
                                    $('#airly').remove();
                                    return;
                                }

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
                        let firstload = true;

                        function loadTramData0() {
                            if (!firstload) return;
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
                                <th class="w-1/6"><i class="fa-solid fa-train-tram" style="color: #4A73AF"></i> Linia</th>
                                <th class="w-4/6"><i class="fa-solid fa-location-dot" style="color: #4A73AF"></i> Kierunek</th>
                                <th class="w-1/6"><i class="fa-solid fa-clock" style="color: #4A73AF"></i> Odjazd <br> (w minutach)</th>
                            </tr>
                        </thead>
                        <tbody>`;
                                        if (response.data.length > 0) {
                                            let firstTram = response.data[0];
                                            content += `
                                                <tr id="first" class="text-[28px]">
                                                <td class="text-center"> ${firstTram.line}</td>
                                                <td class="text-center"> ${firstTram.direction}</td>`;
                                            if (firstTram.minutes === 0) {
                                                content += `<td class="text-center"><1</td>`;
                                            } else if (firstTram.minutes === 1) {
                                                content += `<td class="text-center"> ${firstTram.minutes}</td>`;
                                            } else if (firstTram.minutes < 60) {
                                                content += `<td class="text-center"> ${firstTram.minutes}</td>`;
                                            } else {
                                                let hours = Math.floor(firstTram.minutes / 60);
                                                let minutes = firstTram.minutes % 60;
                                                content += `<td class="text-center"> ${hours}h ${minutes}</td>`;
                                            }
                                            content += `</tr>`;
                                        }
                                        content += `
                                                </tbody>
                                            </table>`;
                                        $('#tram-container').html(content);

                                        let firstContainerJs = document.getElementById('first');
                                        if (firstContainerJs) {
                                            let firstStyle = window.getComputedStyle(firstContainerJs);
                                            let firstheight = firstStyle.getPropertyValue('height');
                                            firstheight = removeSuffix(firstheight, "px");
                                            Number(firstheight);
                                            console.log(firstheight);
                                        } else {
                                            console.warn("Element with ID 'first' not found.");
                                        }

                                        firstload = false;

                                    } else {
                                        console.error("Brak danych do wyświetlenia:", response);
                                        let element = document.getElementById("tram-container");
                                        element.classList.add("m-2", "p-2", "text-[20px]");
                                        $('#tram-container').html('<div class="bg-amber-100 border border-yellow-500 rounded-lg flex items-center space-x-2"> <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i><p class="text-yellow-500 text-sm font-medium">Błąd:Brak danych</p></div>');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error("Błąd ładowania AJAX: ", error);
                                    $('#tram-container').html('<div class="bg-amber-100 border border-yellow-500 rounded-lg flex items-center space-x-2"> <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i><p class="text-yellow-500 text-sm font-medium">Błąd ładowania danych tramwajowych.</p></div>');
                                }
                            });
                        }
                        let loadTramDataFromZero = false;
                        function loadTramData() {
                            $.ajax({
                                url: '/display/get_departures',
                                type: 'POST',
                                dataType: 'json',
                                data: { function: 'tramData' },
                                success: function(response) {
                                    console.debug("Ładowanie danych tramwajowych");

                                    try {
                                        response = typeof response === 'string' ? JSON.parse(response) : response;
                                    } catch (e) {
                                        console.error("Błąd parsowania JSON:", e);
                                    }

                                    if (response.is_active === false) {
                                        $('#tram').remove();
                                        return;
                                    }

                                    if (response.success && Array.isArray(response.data)) {
                                        let startIndex = loadTramDataFromZero ? 0 : 1;
                                        let content = '';
                                        let content1 = `<table class="table-fixed w-full"><tbody>`;
                                        let content0 = `
                    <table class="table-fixed w-full">
                        <thead>
                            <tr>
                                <th class="w-1/6"><i class="fa-solid fa-train-tram" style="color: #4A73AF"></i> Linia</th>
                                <th class="w-4/6"><i class="fa-solid fa-location-dot" style="color: #4A73AF"></i> Kierunek</th>
                                <th class="w-1/6"><i class="fa-solid fa-clock" style="color: #4A73AF"></i> Odjazd <br> (w minutach)</th>
                            </tr>
                        </thead>
                        <tbody>`;
                                        if (startIndex === 0) {
                                            content += content0
                                        } else {
                                            content += content1
                                        }
                                        for (let index = startIndex; index < response.data.length && index < 16; index++) {
                                            let tram = response.data[index];
                                            content += `
                                                    <tr class="text-[28px]">
                                                    <td class="text-center w-1/6">${tram.line}</td>
                                                    <td class="text-center w-4/6">${tram.direction}</td>`;

                                            if (tram.minutes === 0) {
                                                content += `<td class="text-center w-1/6"><1</td>`;
                                            } else if (tram.minutes === 1) {
                                                content += `<td class="text-center"> ${tram.minutes}</td>`;
                                            } else if (tram.minutes < 60) {
                                                content += `<td class="text-center w-1/6">${tram.minutes}</td>`;
                                            } else {
                                                let hours = Math.floor(tram.minutes / 60);
                                                let minutes = tram.minutes % 60;
                                                content += `<td class="text-center w-1/6">${hours}h ${minutes}</td>`;
                                            }
                                            content += `</tr>`;
                                        }
                                        content += `</tbody></table>`;

                                        if (startIndex === 1) {
                                            $('#tram-container').append(content);
                                        } else {
                                            $('#tram-container').html(content);
                                        }

                                        if (!loadTramDataFromZero) {
                                            loadTramDataFromZero = true;
                                        }

                                    } else {
                                        console.error("Brak danych do wyświetlenia:", response);
                                        $('#tram-container').html('<div class="bg-amber-100 border border-yellow-500 rounded-lg flex items-center space-x-2"> <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i><p class="text-yellow-500 text-sm font-medium">Błąd: Brak danych</p></div>');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error("Błąd ładowania AJAX: ", error);
                                    $('#tram-container').html('<div class="bg-amber-100 border border-yellow-500 rounded-lg flex items-center space-x-2"> <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i><p class="text-yellow-500 text-sm font-medium">Błąd ładowania danych tramwajowych.</p></div>');
                                }
                            });
                        }

                        let tramContainerJs = document.getElementById('left');
                        let tramStyle = window.getComputedStyle(tramContainerJs);
                        let height = tramStyle.getPropertyValue('height');
                        function removeSuffix(str, suffix) {
                            if (str.endsWith(suffix)) {
                                return str.slice(0, -suffix.length);
                            }
                            return str;
                        }
                        height = removeSuffix(height, "px");
                        Number(height);
                        console.log(height);

                        setInterval(loadTramData, 50000);
                        loadTramData0();
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
                                    $('#countdown').remove();
                                    return;
                                }

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
                                    $('#countdown').html('<p>Błąd: Brak danych</p>');
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error("Błąd ładowania AJAX: ", error);
                                $('#countdown').html('<p>Błąd ładowania danych odliczania.</p>');
                            }
                        });
                    }

                    setInterval(loadCountdownData, 60000);
                    loadCountdownData();
                </script>
                <div id="announcements" class="px-2 py-2 mx-2 my-2">
                    <div id="announcements-container" class="text-[20px]">Ładowanie danych...</div>
                </div>

                <script>
                    let fadeInterval;

                    function displayAnnouncements(announcementsChunk) {
                        const html = announcementsChunk.map(announcement => `
                            <div class="announcement bg-beige rounded-2xl px-2 py-2 shadow-custom mb-4 w-full overflow-x-hidden break-words whitespace-normal">
                            <span class="flex items-baseline">
                                <h3>${announcement.title}</h3>
                            </span>
                                <p>${announcement.text}</p>
                                <p>
                                  <small>
                                    <i class="fa-solid fa-calendar" style="color: #4A73AF"></i> <strong>Utworzono:</strong> ${announcement.date}
                                  </small> –
                                  <small><strong>Ważne do:</strong> ${announcement.validUntil}</small>
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
                                    $('#announcements').remove();
                                    return;
                                }

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

