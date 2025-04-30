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

        <form method="POST" action="/panel/add_countdown" class="mb-6 p-4 bg-white rounded shadow">
            <div class="mb-2">
                <label>
                    <input type="text" name="title" placeholder="tytuł" class="w-full p-2 border rounded" required>
                </label>
            </div>
            <div class="mb-2">
                <label>
                    <input type="date" name="count_to" class="w-full p-2 border rounded" required>
                </label>
            </div>
            <input type="submit" name="add_countdown" value="Add">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars(SessionHelper::get('user_id')) ?>">
        </form>

        <?php if (!empty($countdowns)): ?>
            <table id="countdownsTable" class="min-w-full bg-white border">
                <thead class="bg-gray-200">
                <tr>
                    <th class="px-4 py-2 border">Nazwa wydarzenia</th>
                    <th class="px-4 py-2 border">Autor</th>
                    <th class="px-4 py-2 border" data-type="date" data-format="YYYY-MM-DD">Data</th>
                    <th class="px-4 py-2 border">Akcje</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($countdowns as $countdown): ?>
                    <tr>
                        <td class="px-4 py-2 border"><?= htmlspecialchars($countdown['title']) ?></td>
                        <td class="px-4 py-2 border"><?= htmlspecialchars($countdown['user_id']) ?? "Brak" ?></td>
                        <td class="px-4 py-2 border"><?= htmlspecialchars($countdown['count_to']) ?></td>
                        <td class="px-4 py-2 border space-x-2">
                            <!-- Formularz usunięcia odliczania -->
                            // TODO dodanie obsługi usunięcia odliczania
                            <form method="POST" action="/panel/delete_countdown" class="delete-form inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                                <input type="hidden" name="countdown_id" value="<?= htmlspecialchars($countdown['id']) ?>">

                                <button type="button" class="delete-btn bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded">
                                    Usuń
                                </button>
                            </form>
                            <!-- Formularz edycji odliczania -->
                            <form method="POST" action="/panel/edit_countdown" class="edit-form inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                                <input type="hidden" name="countdown_id" value="<?= htmlspecialchars($countdown['id']) ?>">
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
            <p>Brak odliczań do wyświetlenia.</p>
        <?php endif; ?>

        <?php include('functions/footer.php'); ?>
    </body>
</html>