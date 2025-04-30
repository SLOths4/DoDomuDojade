<?php
namespace src\views;

require_once __DIR__ . '/../../vendor/autoload.php';

use src\core\SessionHelper;

SessionHelper::start();
$error = SessionHelper::get('error');
SessionHelper::remove('error');

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
    <?php include('functions/navbar.php'); ?>

    <form method="POST" action="/panel/add_user" class="mb-6 p-4 bg-white rounded shadow"">
        <div class="mb-2">
            <label>
                <input type="text" name="username" placeholder="Nazwa uzytkownika" class="w-full p-2 border rounded" required>
            </label>
        </div>
        <div class="mb-2">
            <label>
                <input type="text" name="password" placeholder="Hasło" class="w-full p-2 border rounded" required>
            </label>
        </div>
        <input type="submit" name="add_user" value="Dodaj" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars(SessionHelper::get('user_id')) ?>">
    </form>

    <?php if (!empty($users)): ?>
        <?php foreach ($users as $user): ?>
            <div id="announcement">
                <h3><?= htmlspecialchars($user['username']) ?></h3>

                <!-- Formularz usunięcia użytkownika -->
                <form method="POST" action="/panel/delete_user" onsubmit="return confirm('Czy na pewno chcesz usunąć tego użytkownika?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                    <input type="hidden" name="user_to_delete_id" value="<?= htmlspecialchars($user['id']) ?>">
                    <button type="submit" name="delete_user">Usuń</button>
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
                    <button type="submit" name="edit_announcement">Edytuj</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Brak uzytkowników do wyświetlenia.</p>
    <?php endif; ?>

    <?php include('functions/footer.php'); ?>
</body>
</html>