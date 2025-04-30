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

    <?php if (SessionHelper::has('error')): ?>
        <div class="mb-4 p-2 bg-red-100 text-red-700 rounded">
            <?= htmlspecialchars(SessionHelper::get('error')) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/panel/add_announcement" class="mb-6 p-4 bg-white rounded shadow">
        <div class="mb-2">
            <label>
                <input type="text" name="title" placeholder="Tytuł" class="w-full p-2 border rounded" required>
            </label>
        </div>
        <div class="mb-2">
            <label>
                <input type="text" name="text" placeholder="Tekst" class="w-full p-2 border rounded" required>
            </label>
        </div>
        <div class="mb-2">
            <label>
                <input type="date" name="valid_until" placeholder="Wazne do" class="w-full p-2 border rounded" required>
            </label>
        </div>
        <div class="flex items-center justify-between">
            <input type="submit" name="add_announcement" value="Dodaj" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
        </div>
    </form>

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
                    <td class="px-4 py-2 border"><?= htmlspecialchars($announcement['valid_until']) ?></td>
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


<?php include('functions/footer.php'); ?>
</body>
</html>