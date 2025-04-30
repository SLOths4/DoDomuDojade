<?php
namespace src\views;

require_once __DIR__ . '/../../vendor/autoload.php';

use src\core\SessionHelper;

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
<body>

<h1>Witaj, <?= isset($user['username']) ? htmlspecialchars($user['username']) : 'Gościu' ?>!</h1>

<button onclick="location.href = '/logout';"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj się</button>
<button onclick="window.open('/display', '_blank');"><i class="fa-solid fa-display"></i> Wyświetlaj informacje</button>

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

<?php include('functions/footer.php'); ?>
</body>
</html>