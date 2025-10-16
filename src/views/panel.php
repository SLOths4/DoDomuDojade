<?php
namespace src\views;

require_once __DIR__ . '/../../vendor/autoload.php';

use src\infrastructure\helpers\SessionHelper;

SessionHelper::start();
$error = SessionHelper::get('error');
SessionHelper::remove('error');
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Panel | DoDomuDojadę</title>
        <link rel="icon" type="image/x-icon" href="/assets/resources/favicon.ico">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
        <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
        <script src="//unpkg.com/alpinejs" defer></script>
        <link href="/assets/styles/output.css" rel="stylesheet" type="text/css">
    </head>
    <body class="flex flex-col min-h-screen bg-primary-200 dark:bg-primary-400 dark:text-white">
        <?php include('functions/navbar.php'); ?>
        <main class="flex-grow">
            <div class="flex flex-col justify-center items-center shadow-custom rounded-2xl mx-1 bg-white dark:bg-gray-900 dark:text-white space-y-4 p-4">
                <h1 class="justify-center text-2xl">Witaj, <?= isset($user['username']) ? htmlspecialchars($user['username']) : 'Gościu' ?>!</h1>
                <p>Panel to miejsce do zarządzania całym serwisem do DoDomuDojadę. W zakładkach powyżej znajdziesz odpowiednie ustawienia.</p>
                <button onclick="window.open('/display', '_blank');" class="flex items-center gap-2 px-4 py-2 !bg-primary-200 dark:text-white rounded-md hover:!bg-primary-400"><i class="fa-solid fa-display"></i> Wyświetlaj informacje</button>
            </div>
        </main>
        <?php include('functions/footer.php'); ?>
    </body>
</html>