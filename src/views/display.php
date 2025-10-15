<html lang="pl" x-data>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DoDomuDojadę</title>
    <link rel="icon" type="image/x-icon" href="/assets/resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
    <link href="/assets/styles/output.css" rel="stylesheet" type="text/css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-primary-200 font-lato text-gray-800">

<!-- 🔹 GÓRNY PANEL: Data / Czas / Pogoda -->
<div class="flex mx-1 my-2">
    <!-- DATA I CZAS -->
    <div x-data="clock()" x-init="init()" class="flex flex-auto bg-white h-20 rounded-2xl mr-1 shadow-custom justify-around items-center">
        <img src="assets/resources/logo_samo_kolor.png" alt="logo" width="40" height="40">
        <div class="font-mono text-lg md:text-2xl font-extrabold flex items-center space-x-2">
            <i class="fa-solid fa-calendar text-primary-400"></i>
            <span x-text="date"></span>
        </div>
        <div class="font-mono text-lg md:text-2xl font-extrabold flex items-center space-x-2">
            <i class="fa-solid fa-clock text-primary-400"></i>
            <span x-text="time"></span>
        </div>
    </div>

    <!-- POGODA -->
    <div x-data="weather()" x-init="load()" class="flex flex-auto bg-white h-20 rounded-2xl ml-1 shadow-custom justify-around items-center font-mono text-xl font-extrabold">
        <template x-if="loading">
            <p class="text-white">Ładowanie...</p>
        </template>
        <template x-if="error">
            <div class="bg-red-100 mx-3 text-xl border border-red-500 rounded-lg flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-red-500 p-2.5" aria-hidden="true"></i>
                <p class="text-red-500 text-sm font-medium" x-text="error"></p>
            </div>
        </template>
        <template x-if="!loading && !error && data">
            <div class="flex w-full justify-around">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-temperature-three-quarters" :style="`color: ${tempColor}`"></i>
                    <span x-text="`${data.temperature}°C`"></span>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-gauge text-primary-400"></i>
                    <span x-text="`${data.pressure} hPa`"></span>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-air-freshener" :style="`color: ${data.airlyColour}`"></i>
                    <span x-text="data.airlyAdvice"></span>
                </div>
            </div>
        </template>
    </div>
</div>

<!-- 🔹 GŁÓWNY GRID: Tramwaje | Odliczanie + Ogłoszenia -->
<div class="grid grid-flow-col auto-cols-fr w-full overflow-x-auto px-1 gap-2">

    <!-- LEWA KOLUMNA: Tramwaje -->
    <div x-data="tramDepartures()" x-init="load()" class="bg-white rounded-2xl h-full shadow-custom py-2 px-1">
        <template x-if="loading">
            <p class="text-white text-center">Ładowanie danych...</p>
        </template>
        <template x-if="error">
            <div class="bg-red-100 mx-3 text-xl border border-red-500 rounded-lg flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-red-500 p-2.5" aria-hidden="true"></i>
                <p class="text-red-500 text-sm font-medium" x-text="error"></p>
            </div>
        </template>

        <table x-show="!loading && !error && data.length" class="table-fixed w-full">
            <thead>
            <tr class="font-bold text-primary-400 text-lg">
                <th class="w-1/6"><i class="fa-solid fa-train-tram"></i> Linia</th>
                <th class="w-4/6"><i class="fa-solid fa-location-dot"></i> Kierunek</th>
                <th class="w-1/6"><i class="fa-solid fa-clock"></i> Odjazd</th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(tram, index) in data" :key="`${tram.line}-${index}`">
                <tr class="text-center border-t border-gray-200 text-base">
                    <td x-text="tram.line"></td>
                    <td x-text="tram.direction"></td>
                    <td x-html="formatMinutes(tram.minutes)"></td>
                </tr>
            </template>
            </tbody>
        </table>
    </div>

    <!-- ŚRODKOWA KOLUMNA: Odliczanie + Ogłoszenia -->
    <div class="bg-white rounded-2xl h-[800px] shadow-custom py-2 px-2 flex flex-col">

        <!-- ODLICZANIE -->
        <div x-data="countdown()" x-init="load()" class="bg-beige rounded-2xl m-2 p-2 shadow-custom font-mono text-xl font-extrabold text-center">
            <template x-if="loading">
                <p class="text-white">Ładowanie...</p>
            </template>
            <template x-if="error">
                <div class="bg-red-100 mx-3 text-xl border border-red-500 rounded-lg flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-red-500 p-2.5" aria-hidden="true"></i>
                    <p class="text-red-500 text-sm font-medium" x-text="error"></p>
                </div>
            </template>
            <template x-if="!loading && !error && !data">
                <div class="bg-amber-100 mx-3 text-xl border border-yellow-500 rounded-lg flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i>
                    <p class="text-yellow-500 text-sm font-medium">Brak odliczań do wyświetlania</p>
                </div>
            </template>
            <template x-if="!loading && !error && data">
                <p x-text="message"></p>
            </template>
        </div>

        <!-- OGŁOSZENIA -->
        <div x-data="announcements()" x-init="load()" class="flex-1 overflow-y-auto py-2">
            <template x-if="loading">
                <p class="text-white text-center">Ładowanie...</p>
            </template>
            <template x-if="error">
                <div class="bg-red-100 mx-3 text-xl border border-red-500 rounded-lg flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-red-500 p-2.5" aria-hidden="true"></i>
                    <p class="text-red-500 text-sm font-medium" x-text="error"></p>
                </div>
            </template>
            <template x-if="!loading && !error && !announcements.length">
                <div class="bg-amber-100 mx-3 text-xl border border-yellow-500 rounded-lg flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i>
                    <p class="text-yellow-500 text-sm font-medium">Brak ogłoszeń do wyświetlania</p>
                </div>
            </template>

            <template x-for="(group, index) in grouped" :key="index">
                <div x-show="current === index" x-transition>
                    <template x-for="a in group" :key="a.id">
                        <div class="bg-beige rounded-2xl p-3 shadow-custom mb-4">
                            <h3 class="font-bold text-lg" x-text="a.title"></h3>
                            <p class="text-lg" x-text="a.text"></p>
                            <p class="text-sm text-gray-600 mt-1">
                                <i class="fa-solid fa-calendar text-primary-400"></i>
                                <strong>Utworzono:</strong> <span x-text="a.date"></span>,
                                <strong>Ważne do:</strong> <span x-text="a.validUntil"></span>
                                <i class="fa-solid fa-user pl-2 text-primary-400"></i>
                                <span x-text="a.author"></span>
                            </p>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

<!-- 🔹 KOMPONENTY ALPINE -->
<script>
    function clock() {
        return {
            date: '',
            time: '',
            init() {
                this.update();
                setInterval(() => this.update(), 1000);
            },
            update() {
                const now = new Date();
                this.date = now.toLocaleDateString('pl-PL');
                this.time = now.toLocaleTimeString('pl-PL');
            }
        }
    }

    function weather() {
        return {
            data: null,
            tempColor: '#000',
            error: null,
            loading: true,
            async load() {
                this.loading = true;
                this.error = null;
                try {
                    const res = await fetch('/display/get_weather', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ function: 'weatherData' })
                    });
                    const json = await res.json();
                    if (!json.success || !json.data) {
                        this.error = 'Błąd ładowania pogody';
                        this.loading = false;
                        return;
                    }
                    this.data = json.data;
                    const t = json.data.temperature;
                    this.tempColor = t <= -10 ? '#AECBFA' :
                        t <= 0 ? '#A0F0ED' :
                            t <= 5 ? '#000000' :
                                t <= 15 ? '#FFF9A6' :
                                    t <= 25 ? '#FFD1A4' : '#FFB3B3';
                    this.loading = false;
                } catch (e) {
                    this.error = 'Błąd ładowania pogody';
                    this.loading = false;
                    console.error(e);
                }
                setTimeout(() => this.load(), 900000);
            }
        }
    }

    function tramDepartures() {
        return {
            data: [],
            error: null,
            loading: true,
            async load() {
                this.loading = true;
                this.error = null;
                try {
                    const res = await fetch('/display/get_departures', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ function: 'tramData' })
                    });
                    const json = await res.json();
                    if (json.success && Array.isArray(json.data))
                        this.data = json.data.slice(0, 25);
                    else
                        this.error = 'Błąd ładowania odjazdów';
                    this.loading = false;
                } catch (e) {
                    this.error = 'Błąd ładowania odjazdów';
                    this.loading = false;
                    console.error(e);
                }
                setTimeout(() => this.load(), 50000);
            },
            formatMinutes(min) {
                if (min === 0) return '<1';
                if (min < 60) return `${min}`;
                const h = Math.floor(min / 60), m = min % 60;
                return `${h}h ${m}`;
            }
        }
    }

    function countdown() {
        return {
            data: null,
            message: '',
            error: null,
            loading: true,
            intervalId: null,
            async load() {
                this.loading = true;
                this.error = null;
                try {
                    const res = await fetch('/display/get_countdown', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ function: 'countdownData' })
                    });
                    const json = await res.json();
                    if (json.success && json.data?.length) {
                        this.data = json.data[0];
                        this.update();
                        if (this.intervalId) clearInterval(this.intervalId);
                        this.intervalId = setInterval(() => this.update(), 1000);
                    } else if(!json.data?.length) {
                        this.error = 'Brak danych odliczania';
                        if (this.intervalId) clearInterval(this.intervalId);
                    } else if(!json.success) {
                        this.error = 'Błąd ładowania odliczania';
                        if (this.intervalId) clearInterval(this.intervalId);
                    }
                    this.loading = false;
                } catch (e) {
                    this.error = 'Błąd ładowania odliczania';
                    if (this.intervalId) clearInterval(this.intervalId);
                    this.loading = false;
                    console.error('Fetch error:', e);
                }
            },
            update() {
                if (!this.data) return;
                const end = new Date(this.data.count_to * 1000);
                const diff = end - Date.now();
                if (diff <= 0) {
                    this.message = 'Zakończono!';
                    if (this.intervalId) {
                        clearInterval(this.intervalId);
                        this.intervalId = null;
                    }
                    return;
                }
                const d = Math.floor(diff / 86400000),
                    h = Math.floor(diff / 3600000) % 24,
                    m = Math.floor(diff / 60000) % 60,
                    s = Math.floor(diff / 1000) % 60;
                this.message = `Do ${this.data.title} zostało ${d} dni ${h} godzin ${m} minut ${s} sekund.`;
            }
        }
    }


    function announcements() {
        return {
            announcements: [],
            grouped: [],
            current: 0,
            error: null,
            loading: true,
            async load() {
                this.loading = true;
                this.error = null;
                try {
                    const res = await fetch('/display/get_announcements', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ function: 'announcementsData' })
                    });
                    const json = await res.json();
                    if (json.success && json.data?.length) {
                        this.announcements = json.data;
                        this.grouped = this.chunk(json.data, 4);
                        this.rotate();
                    } else if (!json.success) {
                        this.error = 'Błąd ładowania ogłoszeń';
                    }
                    this.loading = false;
                } catch (e) {
                    this.error = 'Błąd ładowania ogłoszeń';
                    this.loading = false;
                    console.error(e);
                }
                setTimeout(() => this.load(), 60000);
            },
            chunk(arr, size) {
                return Array.from({ length: Math.ceil(arr.length / size) },
                    (_, i) => arr.slice(i * size, i * size + size));
            },
            rotate() {
                setInterval(() => {
                    this.current = (this.current + 1) % this.grouped.length;
                }, 8000);
            }
        }
    }
</script>

</body>
</html>
