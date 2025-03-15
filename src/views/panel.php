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
    <link rel="icon" type="image/x-icon" href="../../public/assets/resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link href="/assets/styles/style.css" rel="stylesheet" type="text/css">
    <link href="/assets/styles/admin.css" rel="stylesheet" type="text/css">
    <link href="/assets/styles/output.css" rel="stylesheet" type="text/css">
    <script src="/scripts/panel.js"></script>
    <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
</head>
<body>

<h1>Witaj, <?= isset($user['username']) ? htmlspecialchars($user['username']) : 'Gościu' ?>!</h1>

<button onclick="location.href = '/logout';"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj się</button>
<button onclick="window.open('/display', '_blank');"><i class="fa-solid fa-display"></i> Wyświetlaj informacje</button>

<div id="announcement">
    <?php
    if (SessionHelper::has('error')) {
        echo '<div class="error">' . SessionHelper::get('error') . '</div>';
    }
    ?>

    <form method="POST" action="panel/add_announcement">
        <label>
            <input type="text" name="title" placeholder="Title">
        </label>
        <label>
            <input type="text" name="text" placeholder="Text">
        </label>
        <label>
            <input type="date" name="valid_until" placeholder="Valid until">
        </label>
        <input type="submit" name="add_announcement" value="Add">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
    </form>

    <?php if (!empty($announcements)): ?>
        <?php foreach ($announcements as $announcement): ?>
            <div id="announcement">
                <h3><?= htmlspecialchars($announcement['title']) ?></h3>
                <p>Autor: <?= htmlspecialchars($announcement['user_id']) ?> | <?= htmlspecialchars($announcement['date']) ?></p>
                <p>Ważne do: <?= htmlspecialchars($announcement['valid_until']) ?></p>
                <p><?= htmlspecialchars($announcement['text']) ?></p>

                <!-- Formularz usunięcia ogłoszenia -->
                <form method="POST" action="/panel/delete_announcement" onsubmit="return confirm('Czy na pewno chcesz usunąć to ogłoszenie?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                    <input type="hidden" name="announcement_id" value="<?= htmlspecialchars($announcement['id']) ?>">
                    <button type="submit" name="delete_announcement" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">Usuń</button>
                </form>

                <!-- Modal potwierdzenia -->
                <div id="confirmationModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
                    <div class="bg-white p-6 rounded shadow-lg max-w-sm w-full">
                        <h2 class="text-xl font-semibold mb-4">Potwierdzenie usunięcia</h2>
                        <p class="mb-6">Czy na pewno chcesz usunąć to ogłoszenie?</p>
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

                <!-- Formularz edycji ogłoszenia -->
                <form method="POST" action="/panel/edit_announcement">
                    <label>
                        <input type="text" name="title" value="<?= htmlspecialchars($announcement['title']) ?>">
                    </label>
                    <label>
                        <input type="text" name="text" value="<?= htmlspecialchars($announcement['text']) ?>">
                    </label>
                    <label>
                        <input type="date" name="valid_until" value="<?= htmlspecialchars($announcement['valid_until']) ?>">
                    </label>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                    <input type="hidden" name="announcement_id" value="<?= htmlspecialchars($announcement['id']) ?>">
                    <button type="submit" name="edit_announcement">Edytuj</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Brak ogłoszeń do wyświetlenia.</p>
    <?php endif; ?>
</div>

<div id="users">
    <form method="POST" action="panel/add_user">
        <label>
            <input type="text" name="username" placeholder="Username">
        </label>
        <label>
            <input type="text" name="password" placeholder="Password">
        </label>
        <input type="submit" name="add_user" value="Add">
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
        <p>Brak ogłoszeń do wyświetlenia.</p>
    <?php endif; ?>
</div>

<div id="modules">
    <?php if (!empty($modules)): ?>
        <?php foreach ($modules as $module): ?>
            <div id="module">
                <h3><?= htmlspecialchars($module['module_name']) ?></h3>
                <p>Status: <?= ($module['is_active'] ? "Włączony" : "Wyłączony") ?></p>

                <!-- Formularz włączania/wyłączania modułu -->
                <form method="POST" action="/panel/toggle_module" onsubmit="return confirm('Czy na pewno chcesz zmienić stan modułu?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                    <input type="hidden" name="module_name" value="<?= htmlspecialchars($module['module_name']) ?>">
                    <input type="hidden" name="enable" value="<?= $module['is_active'] ? '0' : '1' ?>">
                    <button type="submit"><?= $module['is_active'] ? "Wyłącz" : "Włącz" ?></button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Brak ogłoszeń do wyświetlenia.</p>
    <?php endif; ?>

</div>

<!-- IMPORT FOOTER -->
<?php include('functions/footer.php'); ?>
</body>
</html>