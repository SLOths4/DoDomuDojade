<div id="menu" class="fixed inset-0 z-50 flex justify-center items-center
                     bg-gray-900 opacity-0 pointer-events-none duration-700">
    <a href="javascript:void(0)"
       class="absolute top-6 right-8 text-white hover:text-amber-500 text-7xl font-semibold duration-300"
       onclick="closeMenu()">&times;</a>
    <div class="flex flex-col text-white text-center text-xl font-light space-y-3">
        <a class="hover:text-amber-500 duration-300" href="/panel">Panel</a>
        <a class="hover:text-amber-500 duration-300" href="/panel/announcements">Ogłoszenia</a>
        <a class="hover:text-amber-500 duration-300" href="/panel/countdowns">Odliczania</a>
        <a class="hover:text-amber-500 duration-300" href="/panel/users">Użytkownicy</a>
        <a class="hover:text-amber-500 duration-300" href="/panel/modules">Moduły</a>
    </div>
</div>

<div id="navbar" class="my-2 mx-1 p-2 sticky top-0 right-0 left-0 flex z-10 bg-white dark:bg-gray-800 items-center rounded-2xl">
    <a href="/panel"><img class="m-2" src="/assets/resources/logo_samo_kolor.png" alt="logo" width="40" height="40" ></a>
    <a class="max-sm:hidden sm:max-md:hidden" href="/panel"><p class="font-extrabold dark:text-white pl-1 pr-2">DoDomuDojadę</p></a>
    <ul class="flex m-0 p-0 z-10 list-none">
        <li>
            <a href="javascript:void(0)" onclick="openMenu()"
               class="max-lg:block lg:hidden text-center px-4 py-3 bg-white hover:bg-beige dark:bg-gray-800 dark:hover:bg-gray-900 dark:text-white duration-300 rounded-2xl">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="img" aria-label="Menu" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18M3 12h18M3 18h18" />
                </svg>
            </a>
        </li>
        <li>
            <a href="/panel" class="nav-link block max-sm:hidden sm:max-lg:hidden dark:text-white text-center px-4 py-3 bg-white hover:bg-beige dark:bg-gray-800 dark:hover:bg-gray-900 rounded-2xl">Panel</a>
        </li>
        <li>
            <a href="/panel/announcements" class="nav-link block max-sm:hidden sm:max-lg:hidden dark:text-white text-center px-4 py-3 bg-white hover:bg-beige dark:bg-gray-800 dark:hover:bg-gray-900 rounded-2xl">Ogłoszenia</a>
        </li>
        <li>
            <a href="/panel/countdowns" class="nav-link block max-sm:hidden sm:max-lg:hidden  dark:text-white text-center px-4 py-3 bg-white hover:bg-beige dark:bg-gray-800 dark:hover:bg-gray-900 rounded-2xl">Odliczania</a>
        </li>
        <li>
            <a href="/panel/users" class="nav-link block max-sm:hidden sm:max-lg:hidden dark:text-white text-center px-4 py-3 bg-white hover:bg-beige dark:bg-gray-800 dark:hover:bg-gray-900 rounded-2xl">Użytkownicy</a>
        </li>
        <li>
            <a href="/panel/modules" class="nav-link block max-sm:hidden sm:max-lg:hidden darl:text-white text-center px-4 py-3 bg-white hover:bg-beige dark:bg-gray-800 dark:hover:bg-gray-900 rounded-2xl">Moduły</a>
        </li>
    </ul>
    <script>
        const currentPage = window.location.pathname;

        document.querySelectorAll('.nav-link').forEach(link => {
            if (link.getAttribute('href') === currentPage && window.matchMedia("(prefers-color-scheme: dark)").matches) {
                link.classList.remove('bg-gray-800', 'hover:bg-gray-900');
                link.classList.add('hover:bg-primary-400', 'bg-primary-200');
            } else if (link.getAttribute('href') === currentPage && window.matchMedia("(prefers-color-scheme: light)").matches) {
                link.classList.remove('bg-white', 'hover:bg-beige');
                link.classList.add('hover:bg-primary-400', 'bg-primary-200');
            }
        });

        var menu = document.getElementById("menu");

        // this function is used to open the menu
        function openMenu() {
            menu.classList.remove("opacity-0", "pointer-events-none");
            menu.classList.add("opacity-95");
        }

        function closeMenu() {
            menu.classList.add("opacity-0", "pointer-events-none");
            menu.classList.remove("opacity-95");
        }

        let dropdown = () => ({
            open: false,

            dropdownToggle: {
                ["x-on:click"]() {
                    this.open = ! this.open
                },
            },

            dropdownMenu: {
                ["x-transition:enter"]: "transition ease-out duration-100",
                ["x-transition:enter-start"]: "transform opacity-0 scale-95",
                ["x-transition:enter-end"]: "transform opacity-100 scale-100",
                ["x-transition:leave"]: "transition ease-in duration-75",
                ["x-transition:leave-start"]: "transform opacity-100 scale-100",
                ["x-transition:leave-end"]: "transform opacity-0 scale-95",
                ["x-on:click.outside"]() {
                    this.open = false
                },
                ["x-show"]() {
                    return this.open
                },
            }
        })

        window.addEventListener("DOMContentLoaded", (event) => {
            Alpine.data("dropdown", dropdown)
        })
    </script>
    <div id="profile" class="absolute right-0 z-10 max-w-md origin-top-right bg-white dark:bg-gray-800 flex items-center rounded-2xl mr-2">
        <div id="profile-picture" class="rounded-md max-w-min max-h-min px-2"><i class="fa-solid fa-circle-user fa-2xl dark:text-white"></i></div>
        <div x-data="dropdown">
            <div class="mx-2 pr-2">
                <button x-bind="dropdownToggle" type="button" class="items-center justify-center w-full rounded-lg px-2 py-2 hover:text-primary-400 dark:text-white dark:hover:text-primary-200" id="menu-button" aria-expanded="true" aria-haspopup="true"><?= isset($user['username']) ? htmlspecialchars($user['username']) : 'Gościu' ?></button>
            </div>
            <div x-bind="dropdownMenu" x-cloak class="flex justify-center origin-top-right absolute right-0 mt-4 w-full rounded-2xl border-2 border-primary-400 bg-white dark:bg-gray-800" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                <div class="py-3" role="none">
                    <button class="bg-primary-200 mx-2 px-2 rounded-lg text-gray-800 text-sm hover:bg-primary-400 hover:text-gray-900" onclick="location.href = '/logout';"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj się</button>
                </div>
            </div>
        </div>
    </div>
</div>
