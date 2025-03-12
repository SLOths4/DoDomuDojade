<?php

namespace src\views;

require_once __DIR__ . '/../../vendor/autoload.php';

use src\core\SessionHelper;

SessionHelper::start();
$error = SessionHelper::get('error');
SessionHelper::remove('error');

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <title>Login</title>
    <link href="assets/styles/output.css" rel="stylesheet" type="text/css">
    <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
</head>
<body class="dark:bg-gray-900 dark:text-white">
    <h2 class="block mb-5 mt-5 text-3xl font-medium dark:text-white text-center">Login</h2>
    <form class="max-w-sm mx-auto" method="POST" action="panel/authenticate">
        <div class="mb-5">
            <label for="username" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Username</label>
            <input name="username" type="text" id="username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="" required>
        </div>
        <div class="mb-5">
            <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password</label>
            <input name="password" type="password" id="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="" required>
        </div>
        <?php if ($error): ?>
            <div class="mb-5 bg-red-200 border border-red-800 rounded-lg flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-red-500 p-2.5"></i>
                <p class="text-red-500 text-sm font-medium"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token', '')) ?>">
        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Login</button>
    </form>
</body>
</html>