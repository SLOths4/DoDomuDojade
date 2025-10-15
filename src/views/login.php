<?php

namespace src\views;

require_once __DIR__ . '/../../vendor/autoload.php';

use src\infrastructure\helpers\SessionHelper;

SessionHelper::start();
$error = SessionHelper::get('error');
SessionHelper::remove('error');

?>
<!DOCTYPE html>
<html lang="pl">
    <head>
        <title>DoDomuDojade | Login</title>
        <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="/assets/styles/dist/output.css" rel="stylesheet" type="text/css">
    </head>
    <body class="flex flex-col min-h-screen text-center bg-primary-200 dark:bg-gray-800 dark:text-white">
        <main class="flex-grow">
            <h2 class="block my-5 text-3xl font-medium dark:text-white text-center">Panel</h2>
            <form class="p-2 rounded-2xl max-w-sm mx-auto shadow-custom bg-white dark:bg-gray-900 dark:text-white" method="POST" action="panel/authenticate">
                <div class="mb-5">
                    <label for="username" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nazwa użytkownika</label>
                    <input name="username" type="text" id="username" autocomplete="on" class="text-center bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:!ring-primary-400 focus:!border-primary-400 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="" required>
                </div>
                <div class="mb-5">
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Hasło</label>
                    <input name="password" type="password" id="password" autocomplete="on" class="text-center bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:!ring-primary-400 focus:!border-primary-400 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="" required>
                </div>
                <?php if ($error): ?>
                    <div class="mb-5 bg-red-200 border border-red-800 rounded-lg flex items-center space-x-2">
                        <i class="fa-solid fa-triangle-exclamation text-red-500 p-2.5"></i>
                        <p class="text-red-500 text-sm font-medium"><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token', '')) ?>">
                <button type="submit" class="shadow-custom dark:text-white bg-primary-200 hover:bg-primary-400 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center">Zaloguj się</button>
            </form>
        </main>
        <?php include('functions/footer.php'); ?>
    </body>
</html>
