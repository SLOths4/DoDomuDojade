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
        <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/flowbite@1.6.5/dist/flowbite.min.css" />
        <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
        <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
        <script src="//unpkg.com/alpinejs" defer></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://unpkg.com/flowbite@1.6.5/dist/flowbite.min.js"></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="/assets/styles/dist/output.css" rel="stylesheet" type="text/css">
    </head>
    <body class="flex flex-col min-h-screen bg-primary-200 dark:bg-primary-400 dark:text-white">
        <?php include('functions/navbar.php'); ?>
        <main class="flex-grow">
            <form method="POST" action="/panel/add_countdown" class="mb-6 p-4 bg-white dark:bg-gray-900 dark:text-white rounded-2xl shadow-custom mx-1">
                <div class="mb-2">
                    <label>
                        <input type="text" id="add_title" name="title" placeholder="Tytuł" class="w-full p-2 border rounded dark:bg-gray-950 dark:text-white" maxlength="50" required>
                        <span id="add_title_counter" class="text-sm text-gray-600 dark:text-gray-400">0 / 50 znaków</span>
                    </label>
                </div>
                <div class="mb-2">
                    <label>
                        <input type="datetime-local" name="count_to" class="w-full p-2 border rounded dark:bg-gray-950 dark:text-white" required>
                    </label>
                </div>
                <input type="submit" name="add_countdown" value="Dodaj" class="hover:!bg-primary-400 !bg-primary-200 text-white px-4 py-2 rounded">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars(SessionHelper::get('user_id')) ?>">
            </form>

            <?php if (!empty($countdowns)): ?>
            <div class="mx-1 mb-2 rounded-2xl overflow-hidden shadow bg-white dark:bg-gray-900 dark:text-white">
                <table id="countdownsTable" class="w-full table-fixed border-collapse">
                    <thead class="bg-gray-200 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 border">Nazwa wydarzenia</th>
                        <th class="px-4 py-2 border">Autor</th>
                        <th class="px-4 py-2 border" data-type="datetime-local">Data</th>
                        <th class="px-4 py-2 border">Akcje</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($countdowns as $countdown): ?>
                        <tr>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($countdown['title']) ?></td>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($users[$countdown['user_id']]['username'] ?? "Nieznany użytkownik")?></td>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($countdown['count_to']) ?></td>
                            <td class="px-4 py-2 border space-x-2">
                                <button type="button"
                                        class="delete-btn bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded"
                                        data-countdown-id="<?= htmlspecialchars($countdown['id']) ?>">
                                    Usuń
                                </button>
                                <button type="button"
                                        class="edit-btn bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-2 rounded"
                                        data-countdown-id="<?= htmlspecialchars($countdown['id']) ?>"
                                        data-title="<?= htmlspecialchars($countdown['title']) ?>"
                                        data-count-to="<?= htmlspecialchars($countdown['count_to']) ?>">
                                    Edytuj
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="bg-amber-100 mx-3 text-[20px] border border-yellow-500 rounded-lg flex items-center space-x-2"> <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i><p class="text-yellow-500 text-sm font-medium">Brak odliczań do wyświetlania</p></div>
    <?php endif; ?>

            <div id="confirmationModal" class="fixed inset-0 flex items-center justify-center hidden z-50">
                <div class="absolute inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm"></div>

                <div class="relative bg-white p-6 rounded shadow-lg max-w-sm w-full z-10 dark:bg-gray-800 dark:text-white">
                    <h2 class="text-xl font-semibold mb-4">Potwierdzenie usunięcia</h2>
                    <p class="mb-6">Czy na pewno chcesz usunąć to odliczanie? Tej operacji nie można cofnąć.</p>
                    <div class="flex justify-end">
                        <button id="cancelDeleteBtn" class="mr-4 px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                            Anuluj
                        </button>
                        <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                            Usuń
                        </button>
                    </div>
                </div>

                <form method="POST" action="/panel/delete_countdown" class="delete-form hidden">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                    <input type="hidden" name="countdown_id" value="">
                </form>
            </div>

            <div id="editionModal" class="fixed inset-0 flex items-center justify-center hidden z-50">
                <div class="absolute inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm"></div>

                <div class="relative bg-white p-6 rounded shadow-lg max-w-md w-full z-10 dark:bg-gray-800 dark:text-white">
                    <h2 class="text-xl font-semibold mb-4">Edytuj ogłoszenie</h2>
                    <form method="POST" action="/panel/edit_countdown" id="editCountdownForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                        <input type="hidden" id="edit_countdown_id" name="countdown_id">

                        <div class="mb-4">
                            <label for="edit_title" class="block text-sm font-medium text-gray-700 dark:text-white">Tytuł</label>
                            <input type="text" id="edit_title" maxlength="50" name="title" class="w-full p-2 border rounded dark:bg-gray-950 dark:text-white" required>
                            <span id="title_char_counter" class="text-sm text-gray-600 dark:text-gray-400">
                                0 / 50 znaków
                            </span>
                        </div>

                        <div class="mb-4">
                            <label for="edit_count_to" class="block text-sm font-medium text-gray-700 dark:text-white">Odliczaj do</label>
                            <input type="datetime-local" id="edit_count_to" name="count_to" class="w-full p-2 border rounded dark:bg-gray-950 dark:text-white" required>
                        </div>

                        <div class="flex justify-end">
                            <button type="button" id="cancelEditBtn" class="mr-4 px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                                Anuluj
                            </button>
                            <button type="submit" class="px-4 py-2 !bg-primary-200 text-white rounded hover:!bg-primary-400">
                                Zapisz
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const titleCounter = document.getElementById("title_char_counter");
                    const titleInput = document.getElementById("edit_title");
                    const addTitleInput = document.getElementById("add_title");
                    const addTitleCounter = document.getElementById("add_title_counter");

                    const modals = {
                        confirmation: document.getElementById('confirmationModal'),
                        edition: document.getElementById('editionModal')
                    };

                    const forms = {
                        delete: document.querySelector('#confirmationModal form.delete-form'),
                        edit: document.getElementById('editCountdownForm')
                    };

                    const buttons = {
                        cancelDelete: document.getElementById('cancelDeleteBtn'),
                        confirmDelete: document.getElementById('confirmDeleteBtn'),
                        cancelEdit: document.getElementById('cancelEditBtn'),
                        confirmEdit: document.getElementById('confirmEditBtn')
                    };

                    const toggleModal = (modal, show) => {
                        modal.classList.toggle('hidden', !show);
                    };

                    const addClickEventToButtons = (selector, callback) => {
                        document.querySelectorAll(selector).forEach(btn => {
                            btn.addEventListener('click', callback);
                        });
                    };

                    const handleDeleteButtonClick = (event) => {
                        event.preventDefault();
                        forms.delete.querySelector('input[name="countdown_id"]').value = event.currentTarget.getAttribute('data-countdown-id');
                        toggleModal(modals.confirmation, true);
                    };

                    const handleEditButtonClick = (event) => {
                        event.preventDefault();
                        const btn = event.currentTarget;

                        forms.edit.querySelector('#edit_countdown_id').value = btn.getAttribute('data-countdown-id');
                        forms.edit.querySelector('#edit_title').value = btn.getAttribute('data-title');
                        forms.edit.querySelector('#edit_count_to').value = btn.getAttribute('data-count-to');

                        initCounter(titleInput, titleCounter);

                        toggleModal(modals.edition, true);
                    };

                    /**
                     * Inicjalizacja licznika znaków
                     * @param {HTMLElement} field - Pole tekstowe (np. title lub text)
                     * @param {HTMLElement} counter - Licznik znaków (np. dla text czy title)
                     */
                    const updateCounterFactory = (field, counter) => () => {
                        const maxLength = field.getAttribute("maxlength");
                        const currentLength = field.value.length || 0;
                        counter.textContent = `${currentLength} / ${maxLength} znaków`;
                    };

                    const enforceMaxLength = (field) => {
                        const maxLength = field.maxLength;
                        if (field.value.length > maxLength) {
                            field.value = field.value.slice(0, maxLength);
                        }
                    };

                    const initCounter = (field, counter) => {
                        const updateCounter = updateCounterFactory(field, counter);

                        // Usuwamy poprzedni nasłuchiwacz (jeśli był)
                        field.removeEventListener("input", updateCounter);

                        // Dodajemy nowy
                        field.addEventListener("input", () => {
                            enforceMaxLength(field);   // zabezpieczenie
                            updateCounter();           // aktualizacja licznika
                        });

                        // Inicjalizacja przy załadowaniu
                        enforceMaxLength(field);
                        updateCounter();
                    };

                    if (addTitleInput && addTitleCounter) {
                        initCounter(addTitleInput, addTitleCounter);
                    }

                    addClickEventToButtons('.delete-btn', handleDeleteButtonClick);
                    buttons.cancelDelete.addEventListener('click', () => toggleModal(modals.confirmation, false));
                    buttons.confirmDelete.addEventListener('click', () => forms.delete.submit());

                    addClickEventToButtons('.edit-btn', handleEditButtonClick);
                    buttons.cancelEdit.addEventListener('click', () => toggleModal(modals.edition, false));
                    buttons.confirmEdit.addEventListener('click', () => forms.edit.submit());
                });
            </script>
        </main>
        <?php include('functions/footer.php'); ?>
    </body>
</html>
