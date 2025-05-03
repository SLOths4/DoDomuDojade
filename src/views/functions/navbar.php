<div id="navbar" class="sticky top-0 right-0 left-0 flex z-10 bg-gray-800 items-center">
    <img class="m-1" src="/assets/resources/logo_samo_kolor.png" alt="logo" width="40" height="40">
    <p class="font-extrabold text-white px-1">DoDomuDojadę</p>
    <ul class="flex m-0 p-0 z-10 list-none">
        <li>
            <a href="/panel" class="nav-link block text-white text-center px-4 py-3 bg-gray-800 hover:bg-gray-900">Panel</a>
        </li>
        <li>
            <a href="/panel/announcements" class="nav-link block text-white text-center px-4 py-3 bg-gray-800 hover:bg-gray-900">Ogłoszenia</a>
        </li>
        <li>
            <a href="/panel/countdowns" class="nav-link block text-white text-center px-4 py-3 bg-gray-800 hover:bg-gray-900">Odliczania</a>
        </li>
        <li>
            <a href="/panel/users" class="nav-link block text-white text-center px-4 py-3 bg-gray-800 hover:bg-gray-900">Użytkownicy</a>
        </li>
        <li>
            <a href="/panel/modules" class="nav-link block text-white text-center px-4 py-3 bg-gray-800 hover:bg-gray-900 rounded-br-lg">Moduły</a>
        </li>
    </ul>
    <script>
        // Get current file name from URL
        const currentPage = window.location.pathname;

        // Loop through all nav links
        document.querySelectorAll('.nav-link').forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.remove('bg-gray-800', 'hover:bg-gray-900');
                link.classList.add('hover:bg-primary-400', 'bg-primary-200');
            }
        });
    </script>
    <div id="profile" class="absolute right-0 z-10 min-w-48 max-w-md origin-top-right bg-gray-800 flex py-3 rounded-bl-lg">
        <div id="profile-picture" class="rounded-md max-w-min max-h-min px-2"><i class="fa-solid fa-circle-user fa-2xl" style="color: #8ABAE2;"></i></div>
        <div id="profile-name" class="text-primary-200">
            <?= htmlspecialchars($user['username']) ?>
        </div>
        <button class="bg-primary-200 mx-2 px-2 rounded-lg text-gray-800 hover:bg-primary-400 hover:text-gray-900" onclick="location.href = '/logout';"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj się</button>
    </div>
</div>
