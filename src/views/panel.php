<?php
namespace src\views;

require_once __DIR__ . '/../../vendor/autoload.php';

use src\core\SessionHelper;
use DateMalformedStringException;
use DateTime;

SessionHelper::start();
$error = SessionHelper::get('error');
SessionHelper::remove('error');


//-----------mess------

// Ustawianie stanu checkboxa na podstawie przesłania formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['display-valid-announcements-only'])) {
        // Zaznaczenie checkboxa (tylko ważne ogłoszenia)
        $_SESSION['display_valid_announcements_only'] = true;
    } else {
        // Odznaczenie checkboxa (wszystkie ogłoszenia)
        $_SESSION['display_valid_announcements_only'] = false;
    }
}

// ------ mess -------


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Panel | DoDomuDojadę</title>
    <link rel="icon" type="image/x-icon" href="assets/resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link href="/assets/styles/style.css" rel="stylesheet" type="text/css">
    <link href="/assets/styles/admin.css" rel="stylesheet" type="text/css">
    <link href="/assets/styles/output.css" rel="stylesheet" type="text/css">
    <script src="/scripts/panel.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/flowbite@1.6.5/dist/flowbite.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://unpkg.com/flowbite@1.6.5/dist/flowbite.min.js"></script>
</head>
<body class="text-primary-400 dark:text-primary-300 bg-white dark:bg-gray-900">

<h1 class="text-primary-400 dark:text-primary-300">Witaj, <?= isset($user['username']) ? htmlspecialchars($user['username']) : 'Gościu' ?>!</h1>

<button class="p-1" onclick="location.href = '/logout';"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj się</button>
<button class="p-1" onclick="window.open('/display', '_blank');"><i class="fa-solid fa-display"></i> Wyświetlaj informacje</button>

<div id="announcement" class="grid grid-flow-col auto-cols-auto w-full overflow-x-auto mx-auto p-4">
    <?php if (SessionHelper::has('error')): ?>
        <div class="mb-4 p-2 bg-red-100 text-red-700 rounded">
            <?= htmlspecialchars(SessionHelper::get('error')) ?>
        </div>
    <?php endif; ?>

    <div class="items-center">
        <div class="max-w-2xs">
            <!-- Formularz dodawania ogłoszenia -->
            <form method="POST" action="panel/add_announcement" class="mb-6 p-4 bg-beige dark:bg-gray-700 rounded shadow-lg">
                <div class="mb-2">
                    <label>
                        <input type="text" name="title" placeholder="Title" class="w-full p-2 border rounded" required>
                    </label>
                </div>
                <div class="mb-2">
                    <label>
                        <input type="text" name="text" placeholder="Text" class="w-full p-2 border rounded" required>
                    </label>
                </div>
                <div class="mb-2">
                    <label>
                        <input type="date" name="valid_until" placeholder="Valid until" class="w-full p-2 border rounded" required>
                    </label>
                </div>
                <div class="flex items-center justify-center">
                    <input type="submit" name="add_announcement" value="Add" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                </div>
            </form>
        </div>

        <div class="bg-white p-6 rounded shadow-lg max-w-2xs bg-beige dark:bg-gray-700">
            <form method="POST" action="panel/add_user" class="text-center">
                <label>
                    <input class="text-center" type="text" name="username" placeholder="Username">
                </label>
                <label>
                    <input class="text-center" type="text" name="password" placeholder="Password">
                </label>
                <input type="submit" name="add_user" value="Add">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars(SessionHelper::get('user_id')) ?>">
            </form>
        </div>
    </div>
    <div class="mx-2">
        <div class="text-center" id="modules">
            <?php if (!empty($modules)): ?>
                <?php foreach ($modules as $module): ?>
                    <div id="module">
                        <h3><?= htmlspecialchars($module['module_name']) ?></h3>
                        <p>Status: <?= ($module['is_active'] ? "Włączony 🟢" : "Wyłączony 🔴") ?></p>

                        <!-- Formularz włączania/wyłączania modułu -->
                        <form method="POST" action="/panel/toggle_module" onsubmit="return confirm('Czy na pewno chcesz zmienić stan modułu?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                            <input type="hidden" name="module_name" value="<?= htmlspecialchars($module['module_name']) ?>">
                            <input type="hidden" name="enable" value="<?= $module['is_active'] ? '0' : '1' ?>">
                            <button class="p-1" type="submit"><?= $module['is_active'] ? "Wyłącz" : "Włącz" ?></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Brak ogłoszeń do wyświetlenia.</p>
            <?php endif; ?>

        </div>
    </div>
    <div>
        <?php if (!empty($announcements)): ?>
            <table id="announcementsTable" class="min-w-full bg-white border">
                <thead class="bg-gray-200">
                <tr>
                    <th class="px-4 py-2 border">Tytuł</th>
                    <th class="px-4 py-2 border">Autor / Data</th>
                    <th class="px-4 py-2 border" data-type="date" data-format="YYYY-MM-DD">Ważne do</th>
                    <th class="px-4 py-2 border">Treść</th>
                    <th class="px-4 py-2 border">Akcje</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($announcements as $announcement): ?>
                    <tr>
                        <td class="px-4 py-2 border"><?= htmlspecialchars($announcement['title']) ?></td>
                        <td class="px-4 py-2 border">
                            Autor: <?= htmlspecialchars($announcement['user_id']) ?><br>
                            <?= htmlspecialchars($announcement['date']) ?>
                        </td>
                        <td class="px-4 py-2 border"><?= htmlspecialchars($announcement['valid_until']) ?>
                            <?php
                            $validDate = new DateTime(htmlspecialchars($announcement['valid_until']));
                            $now = new DateTime('today midnight');
                            echo $validDate->format('d.m.Y');;
                            echo ($validDate >= $now ? '🟢' : '🔴' );
                            ?></td>
                        <td class="px-4 py-2 border"><?= htmlspecialchars($announcement['text']) ?></td>
                        <td class="px-4 py-2 border space-x-2">
                            <!-- Formularz usunięcia ogłoszenia -->
                            <form method="POST" action="/panel/delete_announcement" class="delete-form inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                                <input type="hidden" name="announcement_id" value="<?= htmlspecialchars($announcement['id']) ?>">

                                <button type="button" class="delete-btn bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded">
                                    Usuń
                                </button>
                            </form>
                            <!-- Formularz edycji ogłoszenia -->
                            <form method="POST" name="edit_announcement" action="/panel/edit_announcement" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                                <input type="hidden" name="announcement_id" value="<?= htmlspecialchars($announcement['id']) ?>">
                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-2 rounded">
                                    Edytuj
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Brak ogłoszeń do wyświetlenia.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal potwierdzenia (umieszczony poza pętlą) -->
<div id="confirmationModal" class="fixed inset-0 flex items-center justify-center hidden z-50">
    <!-- Tło z efektem rozmycia -->
    <div class="absolute inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm"></div>
    <!-- Okno modala -->
    <div class="relative bg-white p-6 rounded shadow-lg max-w-sm w-full z-10">
        <h2 class="text-xl font-semibold mb-4">Potwierdzenie usunięcia</h2>
        <p class="mb-6">Czy na pewno chcesz usunąć to ogłoszenie? Tej operacji nie można cofnąć.</p>
        <div class="flex justify-end">
            <button id="cancelBtn" class="mr-4 px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                Anuluj
            </button>
            <button id="confirmBtn" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                Usuń
            </button>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {
        // Inicjalizacja DataTables dla tabeli ogłoszeń
        $('#announcementsTable').DataTable(
        {
            scrollY: "300px",
        });

        let formToSubmit = null;

        // Obsługa kliknięcia przycisku "Usuń" w tabeli
        $('.delete-btn').on('click', function(e) {
            e.preventDefault();
            formToSubmit = $(this).closest('.delete-form');
            $('#confirmationModal').removeClass('hidden');
        });

        // Zamknięcie modala przy kliknięciu "Anuluj"
        $('#cancelBtn').on('click', function() {
            $('#confirmationModal').addClass('hidden');
            formToSubmit = null;
        });

        // Obsługa potwierdzenia usunięcia
        $('#confirmBtn').on('click', function() {
            if (formToSubmit) {
                formToSubmit.submit();
            }
            $('#confirmationModal').addClass('hidden');
        });

        // Opcjonalnie: zamknięcie modala przy kliknięciu na tło
        $('#confirmationModal').on('click', function(e) {
            if (e.target.id === 'confirmationModal') {
                $(this).addClass('hidden');
                formToSubmit = null;
            }
        });
    });
</script>

<div id="users" class="grid grid-flow-col auto-cols-fr overflow-x-auto p-4 mx-4 bg-white rounded-2xl shadow-lg">

    <?php if (!empty($users)): ?>
        <?php foreach ($users as $user): ?>
            <div id="announcement" class="mx-2">
                <h3><?= htmlspecialchars($user['username']) ?></h3>

                <!-- Formularz usunięcia użytkownika -->
                <form class="mb-2" method="POST" action="/panel/delete_user" onsubmit="return confirm('Czy na pewno chcesz usunąć tego użytkownika?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                    <input type="hidden" name="user_to_delete_id" value="<?= htmlspecialchars($user['id']) ?>">
                    <button class="p-1" type="submit" name="delete_user">Usuń</button>
                </form>

                <!-- Formularz edycji uzytkownika -->
                <form method="POST" action="/panel/edit_user">
                    <label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>">
                    </label>
                    <label>
                        <input type="text" name="password">
                    </label>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                    <input type="hidden" name="user_to_edit_id" value="<?= htmlspecialchars($user['id']) ?>">
                    <button class="p-1" type="submit" name="edit_announcement">Edytuj</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Brak ogłoszeń do wyświetlenia.</p>
    <?php endif; ?>
</div>

<!-- IMPORT FOOTER -->
<?php include('functions/footer.php'); ?>
</body>
</html>