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
    <body>
    <?php include('functions/navbar.php'); ?>

    <h1>Witaj, <?= isset($user['username']) ? htmlspecialchars($user['username']) : 'Gościu' ?>!</h1>

    <button onclick="location.href = '/logout';"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj się</button>
    <button onclick="window.open('/display', '_blank');"><i class="fa-solid fa-display"></i> Wyświetlaj informacje</button>

    <?php include('functions/footer.php'); ?>
    </body>
</html>