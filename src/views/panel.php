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
        <link rel="icon" type="image/x-icon" href="/assets/resources/favicon.ico">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
        <link href="/assets/styles/output.css" rel="stylesheet" type="text/css">
        <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 dark:bg-gray-800 dark:text-white">
    <?php include('functions/navbar.php'); ?>

    <div class="flex flex-col justify-center items-center rounded-lg bg-gray-100 dark:bg-gray-900 dark:text-white space-y-4 p-4">
        <h1 class="justify-center text-2xl">Witaj, <?= isset($user['username']) ? htmlspecialchars($user['username']) : 'Gościu' ?>!</h1>
        <p>Panel to miejsce do zarządzania całym serwisem do DoDomuDojadę. W zakładkach powyzej znajdziesz odpowiednie ustawienia.</p>
        <button onclick="window.open('/display', '_blank');" class="flex items-center gap-2 px-4 py-2 !bg-primary-200 text-white rounded-md hover:!bg-primary-400"><i class="fa-solid fa-display"></i> Wyświetlaj informacje</button>
    </div>

    <?php include('functions/footer.php'); ?>
    </body>
</html>