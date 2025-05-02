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
    <meta charset="utf-8">
    <title>Panel | DoDomuDojadę</title>
    <link rel="icon" type="image/x-icon" href="assets/resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap" rel="stylesheet">
    <link href="/assets/styles/output.css" rel="stylesheet" type="text/css">

    <link rel="stylesheet" href="https://unpkg.com/flowbite@1.6.5/dist/flowbite.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://unpkg.com/flowbite@1.6.5/dist/flowbite.min.js"></script>
</head>
<body class="font-sans">
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
            <input type="date" name="valid_until" placeholder="Ważne do" class="w-full p-2 border rounded" required>
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
                    <!-- Przyciski z atrybutem data-announcement-id -->
                    <button type="button"
                            class="delete-btn bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded"
                            data-announcement-id="<?= htmlspecialchars($announcement['id']) ?>">
                        Usuń
                    </button>
                    <button type="button"
                            class="edit-btn bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-2 rounded"
                            data-announcement-id="<?= htmlspecialchars($announcement['id']) ?>"
                            data-title="<?= htmlspecialchars($announcement['title']) ?>"
                            data-text="<?= htmlspecialchars($announcement['text']) ?>"
                            data-valid-until="<?= htmlspecialchars($announcement['valid_until']) ?>">
                        Edytuj
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Brak ogłoszeń do wyświetlenia.</p>
<?php endif; ?>

<div id="confirmationModal" class="fixed inset-0 flex items-center justify-center hidden z-50">
    <div class="absolute inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm"></div>

    <div class="relative bg-white p-6 rounded shadow-lg max-w-sm w-full z-10">
        <h2 class="text-xl font-semibold mb-4">Potwierdzenie usunięcia</h2>
        <p class="mb-6">Czy na pewno chcesz usunąć to ogłoszenie? Tej operacji nie można cofnąć.</p>
        <div class="flex justify-end">
            <button id="cancelDeleteBtn" class="mr-4 px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                Anuluj
            </button>
            <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                Usuń
            </button>
        </div>
    </div>

    <form method="POST" action="/panel/delete_announcement" class="delete-form hidden">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
        <input type="hidden" name="announcement_id" value="">
    </form>
</div>

<div id="editionModal" class="fixed inset-0 flex items-center justify-center hidden z-50">
    <div class="absolute inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm"></div>

    <div class="relative bg-white p-6 rounded shadow-lg max-w-md w-full z-10">
        <h2 class="text-xl font-semibold mb-4">Edytuj ogłoszenie</h2>
        <form method="POST" action="/panel/edit_announcement" id="editAnnouncementForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
            <input type="hidden" id="edit_announcement_id" name="announcement_id">

            <div class="mb-4">
                <label for="edit_title" class="block text-sm font-medium text-gray-700">Tytuł</label>
                <input type="text" id="edit_title" name="title" class="w-full p-2 border rounded" required>
            </div>

            <div class="mb-4">
                <label for="edit_text" class="block text-sm font-medium text-gray-700">Treść</label>
                <textarea id="edit_text" name="text" class="w-full p-2 border rounded" required></textarea>
            </div>

            <div class="mb-4">
                <label for="edit_valid_until" class="block text-sm font-medium text-gray-700">Ważne do</label>
                <input type="date" id="edit_valid_until" name="valid_until" class="w-full p-2 border rounded" required>
            </div>
            <!-- Akcja: Zapisz / Anuluj -->
            <div class="flex justify-end">
                <button type="button" id="cancelEditBtn" class="mr-4 px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                    Anuluj
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Zapisz
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modals = {
            confirmation: document.getElementById('confirmationModal'),
            edition: document.getElementById('editionModal')
        };

        const forms = {
            delete: document.querySelector('#confirmationModal form.delete-form'),
            edit: document.getElementById('editAnnouncementForm')
        };

        const buttons = {
            cancelDelete: document.getElementById('cancelDeleteBtn'),
            confirmDelete: document.getElementById('confirmDeleteBtn'),
            cancelEdit: document.getElementById('cancelEditBtn'),
            confirmEdit: document.getElementById('confirmEditBtn')
        };

        // Helper: Show & Hide Modal
        const toggleModal = (modal, show) => {
            modal.classList.toggle('hidden', !show);
        };

        // Add event listener to array of buttons
        const addClickEventToButtons = (selector, callback) => {
            document.querySelectorAll(selector).forEach(btn => {
                btn.addEventListener('click', callback);
            });
        };

        // Handle delete button click
        const handleDeleteButtonClick = (event) => {
            event.preventDefault();
            forms.delete.querySelector('input[name="announcement_id"]').value = event.currentTarget.getAttribute('data-announcement-id');
            toggleModal(modals.confirmation, true);
        };

        // Handle edit button click: fill form and show modal
        const handleEditButtonClick = (event) => {
            event.preventDefault();
            const btn = event.currentTarget;

            forms.edit.querySelector('#edit_announcement_id').value = btn.getAttribute('data-announcement-id');
            forms.edit.querySelector('#edit_title').value = btn.getAttribute('data-title');
            forms.edit.querySelector('#edit_text').value = btn.getAttribute('data-text');
            forms.edit.querySelector('#edit_valid_until').value = btn.getAttribute('data-valid-until');
            
            toggleModal(modals.edition, true);
        };

        // Event listeners for delete actions
        addClickEventToButtons('.delete-btn', handleDeleteButtonClick);
        buttons.cancelDelete.addEventListener('click', () => toggleModal(modals.confirmation, false));
        buttons.confirmDelete.addEventListener('click', () => forms.delete.submit());

        // Event listeners for edit actions
        addClickEventToButtons('.edit-btn', handleEditButtonClick);
        buttons.cancelEdit.addEventListener('click', () => toggleModal(modals.edition, false));
        buttons.confirmEdit.addEventListener('click', () => forms.edit.submit());
    });
</script>

<?php include('functions/footer.php'); ?>
</body>
</html>