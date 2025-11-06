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
<body x-data="layout()" x-init="init()" class="bg-primary-200 font-lato text-gray-800">

<!-- 🔹 GÓRNY PANEL: Data / Czas / Pogoda -->
<div x-ref="header" class="flex mx-1 my-2">
    <!-- DATA I CZAS -->
    <div x-data="clock()" x-init="init()" class="flex flex-auto bg-white h-20 rounded-2xl mr-2 shadow-custom justify-around items-center max-sm:hidden">
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
    <div x-data="weather()" x-init="load()" class="flex flex-auto bg-white h-20 rounded-2xl shadow-custom justify-around items-center font-mono text-xl font-extrabold">
        <template x-if="loading">
            <p class="text-center">Ładowanie...</p>
        </template>
        <template x-if="error">
            <div class="bg-red-100 mx-3 text-xl border border-red-500 rounded-lg items-center space-x-2 flex flex-grow w-full">
                <i class="fa-solid fa-triangle-exclamation text-red-500 p-2.5" aria-hidden="true"></i>
                <p class="text-red-500 text-sm font-medium" x-text="error"></p>
            </div>
        </template>
        <template x-if="!loading && !error && data">
            <div class="flex w-full justify-around text-xl">
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
<div class="grid grid-flow-col auto-cols-fr w-full overflow-x-auto px-1 gap-2" :style="`height: ${gridHeight}px;`">

    <!-- LEWA KOLUMNA: Tramwaje -->
    <div x-data="tramDepartures()" x-init="init()" class="bg-white rounded-2xl shadow-custom py-2 px-1 overflow-hidden">
        <template x-if="loading">
            <p class="text-center">Ładowanie danych...</p>
        </template>

        <template x-if="error">
            <div class="bg-red-100 mt-3 mx-3 text-xl border border-red-500 rounded-lg flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-red-500 p-2.5"></i>
                <p class="text-red-500 text-sm font-medium" x-text="error"></p>
            </div>
        </template>

        <div x-show="!loading && !error && data.length">
            <table class="table-fixed w-full">
                <thead>
                <tr class="font-bold text-primary-400 text-lg">
                    <th class="w-1/6 pb-2 text-xs md:max-lg:text-base lg:text-lg">
                        <i class="fa-solid fa-train-tram"></i> Linia
                    </th>
                    <th class="w-4/6 pb-2 text-xs md:max-lg:text-base lg:text-lg">
                        <i class="fa-solid fa-location-dot"></i> Kierunek
                    </th>
                    <th class="w-1/6 pb-2 text-xs md:max-lg:text-base lg:text-lg">
                        <i class="fa-solid fa-clock"></i> Odjazd
                    </th>
                </tr>
                </thead>
                <tbody>
                <template x-for="(tram, index) in data" :key="`${tram.line}-${index}`">
                    <tr class="text-center text-xs md:max-lg:text-base lg:text-lg">
                        <td class="py-2 border-t border-gray-200">
                            <div
                                    x-text="tram.line"
                                    :class="[
                  (count[tram.line] || 'bg-white'),
                  tram.line < 20
                    ? 'h-6 w-6 md:max-lg:h-8 md:max-lg:w-8 lg:h-9 lg:w-9 rounded-full'
                    : 'h-6 w-8 md:max-lg:h-8 md:max-lg:w-10 lg:h-9 lg:w-11 border'
                ]"
                                    class="font-bold inline-flex items-center justify-center"
                            ></div>
                        </td>
                        <td class="py-2 border-t border-gray-200" x-text="tram.direction"></td>
                        <td class="py-2 border-t border-gray-200" x-html="formatMinutes(tram.minutes)"></td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-custom py-2 px-2 flex flex-col">

        <!-- ODLICZANIE -->
        <div x-data="countdown()" x-init="load()">
            <!-- Parent div, hidden when error or info -->
            <div class="bg-beige rounded-2xl m-2 p-2 shadow-custom font-mono text-xl font-extrabold text-center">
                <template x-if="loading">
                    <p class="text-center">Ładowanie...</p>
                </template>
                <template x-if="!loading && data">
                    <p x-text="message"></p>
                </template>
            </div>

            <!-- Error template, outside parent -->
            <template x-if="error">
                <div class="bg-red-100 mt-3 mx-3 text-xl border border-red-500 rounded-lg flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-red-500 p-2.5" aria-hidden="true"></i>
                    <p class="text-red-500 text-sm font-medium" x-text="error"></p>
                </div>
            </template>

            <!-- Info template, outside parent -->
            <template x-if="info">
                <div class="bg-amber-100 mt-3 mx-3 text-xl border border-yellow-500 rounded-lg flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i>
                    <p class="text-yellow-500 text-sm font-medium">Brak odliczań do wyświetlania</p>
                </div>
            </template>
        </div>

        <div x-data="announcements()" x-init="load()">
            <div class="flex-1 overflow-y-auto py-2">
                <template x-if="loading">
                    <p class="text-center text-xl font-mono font-extrabold">Ładowanie...</p>
                </template>
                <template x-if="!loading && announcements">
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
                </template>
            </div>
            <template x-if="error">
                <div class="bg-red-100 mt-3 mx-3 text-xl border border-red-500 rounded-lg flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-red-500 p-2.5" aria-hidden="true"></i>
                    <p class="text-red-500 text-sm font-medium" x-text="error"></p>
                </div>
            </template>
            <template x-if="info">
                <div class="bg-amber-100 mt-3 mx-3 text-xl border border-yellow-500 rounded-lg flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i>
                    <p class="text-yellow-500 text-sm font-medium">Brak ogłoszeń do wyświetlania</p>
                </div>
            </template>
        </div>
    </div>
</div>

<!-- 🔹 KOMPONENTY ALPINE -->
<script>
    function layout() {
        return {
            gridHeight: 0,
            init() {
                this.calcGridHeight();
                window.addEventListener('resize', () => this.calcGridHeight());
                // Delay calculation after fonts and styles settle
                requestAnimationFrame(() => this.calcGridHeight());
            },
            calcGridHeight() {
                const headerRect = this.$refs.header?.getBoundingClientRect() || { height: 0, top: 0, bottom: 0 };
                const marginTop = parseFloat(getComputedStyle(this.$refs.header).marginTop) || 0;
                const marginBottom = parseFloat(getComputedStyle(this.$refs.header).marginBottom) || 0;
                const totalHeaderHeight = headerRect.height + marginTop + marginBottom;

                this.gridHeight = window.innerHeight - totalHeaderHeight; // 8px fudge factor/padding adjustment
            }
        }
    }

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
                    // Data diffing to prevent flashing
                    if (JSON.stringify(this.data) !== JSON.stringify(json.data)) {
                        this.data = json.data;
                        const t = json.data.temperature;
                        this.tempColor = t <= -10 ? '#AECBFA' :
                            t <= 0 ? '#A0F0ED' :
                                t <= 5 ? '#000000' :
                                    t <= 15 ? '#FFF9A6' :
                                        t <= 25 ? '#FFD1A4' : '#FFB3B3';
                    }
                    this.loading = false;
                } catch (e) {
                    this.error = 'Błąd ładowania pogody';
                    this.loading = false;
                    console.error(e);
                }
                setTimeout(() => this.load(), 1000);
            }
        }
    }

    function tramDepartures() {
        return {
            data: [],
            error: null,
            loading: true,
            count: {
                '1': 'bg-pink-700',
                '2': 'bg-2',
                '3': 'bg-green-500',
                '5': 'bg-purple-700',
                '6': 'bg-6',
                '7': 'bg-teal-500',
                '8': 'bg-violet-500',
                '9': 'bg-9',
                '10': 'bg-slate-500',
                '11': 'bg-purple-400',
                '12': 'bg-12',
                '13': 'bg-amber-500',
                '14': 'bg-green-400',
                '15': 'bg-blue-400',
                '16': 'bg-red-400',
                '17': 'bg-red-400',
                '18': 'bg-18',
                '19': 'bg-19',
            },

            async fetchDepartures() {
                this.error = null;
                try {
                    const res = await fetch('/display/get_departures', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ function: 'tramData' })
                    });

                    const json = await res.json();

                    if (json.success && Array.isArray(json.data)) {
                        const newData = json.data.slice(0, 11);
                        if (JSON.stringify(this.data) !== JSON.stringify(newData)) {
                            this.data = newData;
                        }
                    } else {
                        this.error = 'Błąd ładowania odjazdów';
                    }
                } catch (e) {
                    console.error(e);
                    this.error = 'Błąd ładowania odjazdów';
                } finally {
                    this.loading = false;
                }
            },

            formatMinutes(min) {
                if (min === 0) return '<1';
                if (min < 60) return `${min}`;
                const h = Math.floor(min / 60), m = min % 60;
                return `${h}h ${m}`;
            },

            async init() {
                await this.fetchDepartures();
                setInterval(() => this.fetchDepartures(), 60000);
            }
        };
    }

    function countdown() {
        return {
            data: null,
            message: '',
            error: null,
            loading: true,
            info: null,
            intervalId: null,
            async load() {
                try {
                    const res = await fetch('/display/get_countdown', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ function: 'countdownData' })
                    });
                    const json = await res.json();
                    if (json.success && json.data?.length) {
                        if (this.error || this.info) {
                            this.error = null;
                            this.info = null;
                        }
                        this.loading = true;
                        if (JSON.stringify(this.data) === JSON.stringify(json.data[0])) {
                            this.loading = false;
                        }
                        if (JSON.stringify(this.data) !== JSON.stringify(json.data[0])) {
                            this.data = json.data[0];
                            this.loading = false;
                            this.update();
                            if (this.intervalId) clearInterval(this.intervalId);
                            this.intervalId = setInterval(() => this.update(), 1000);
                        }
                    } else if (!json.data?.length) {
                        this.loading = true;
                        this.info = 'Brak danych odliczania';
                        if (this.intervalId) clearInterval(this.intervalId);
                    } else if (!json.success) {
                        this.loading = true;
                        this.error = 'Błąd ładowania odliczania';
                        if (this.intervalId) clearInterval(this.intervalId);
                    }
                } catch (e) {
                    this.error = 'Błąd ładowania odliczania';
                    if (this.intervalId) clearInterval(this.intervalId);
                    this.loading = false;
                    console.error('Fetch error:', e);
                }
                setTimeout(() => this.load(), 300000);
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
            info: null,
            rotateIntervalId: null,
            async load() {
                try {
                    const res = await fetch('/display/get_announcements', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ function: 'announcementsData' })
                    });
                    const json = await res.json();
                    if (json.success && json.data?.length) {
                        if (this.error || this.info) {
                            this.error = null;
                            this.info = null;
                        }
                        this.loading = true;
                        if (JSON.stringify(this.announcements) === JSON.stringify(json.data)) {
                            this.loading = false;
                        }
                        if (JSON.stringify(this.announcements) !== JSON.stringify(json.data)) {
                            this.announcements = json.data;
                            this.loading = false;
                            this.grouped = this.chunk(json.data, 4);
                            if (this.rotateIntervalId) clearInterval(this.rotateIntervalId);
                            this.rotate();
                        }
                    } else if (!json.data?.length) {
                        this.loading = true;
                        this.info = 'Brak danych ogłoszeń';
                        if (this.rotateIntervalId) clearInterval(this.rotateIntervalId);
                    } else if (!json.success) {
                        this.loading = true;
                        this.error = 'Błąd ładowania ogłoszeń';
                        if (this.rotateIntervalId) clearInterval(this.rotateIntervalId);
                    }
                } catch (e) {
                    this.error = 'Błąd ładowania ogłoszeń';
                    this.loading = false;
                    if (this.rotateIntervalId) clearInterval(this.rotateIntervalId);
                    console.error(e);
                }
                setTimeout(() => this.load(), 4000);
            },
            chunk(arr, size) {
                return Array.from({ length: Math.ceil(arr.length / size) },
                    (_, i) => arr.slice(i * size, i * size + size));
            },
            rotate() {
                this.rotateIntervalId = setInterval(() => {
                    this.current = (this.current + 1) % this.grouped.length;
                }, 8000);
            }

        }
    }

</script>

</body>
</html>
