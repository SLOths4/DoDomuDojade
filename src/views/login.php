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
    <link rel="stylesheet" href="assets/styles/style.css">
    <link rel="stylesheet" href="assets/styles/login.css">
</head>
<body>
    <h2>Login</h2>
    <form method="POST" action="panel/authenticate">
        <?php if ($error): ?>
            <p style="color: red;"><?php htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <label for="username"></label><input type="text" name="username" id="username" placeholder="nazwa użytkownika" required>
        <label for="password"></label><input type="password" name="password" id="password" placeholder="hasło" required>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token', '')) ?>">
        <button type="submit">Login</button>
    </form>
</body>
</html>