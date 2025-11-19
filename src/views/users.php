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
        <meta charset="utf-8">
        <title>Panel | DoDomuDojadę</title>
        <link rel="icon" type="image/x-icon" href="assets/resources/favicon.ico">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
        <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
        <script src="//unpkg.com/alpinejs" defer></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <link href="/assets/styles/output.css" rel="stylesheet" type="text/css">
    </head>
    <body class="flex flex-col min-h-screen bg-primary-200 dark:bg-primary-400 dark:text-white">
        <?php include('functions/navbar.php'); ?>
        <main class="flex-grow">
            <?php if (!empty($error)): ?>
                <div id="errorModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
                    <div class="absolute inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm"></div>
                    <div class="relative bg-white p-6 rounded shadow-lg max-w-sm w-full z-10">
                        <h2 class="text-xl font-semibold text-red-700 mb-4">Wystąpił błąd</h2>
                        <p class="text-gray-700 mb-6"><?= htmlspecialchars($error) ?></p>
                        <div class="flex justify-end">
                            <button id="closeErrorModal" class="px-4 py-2 !bg-primary-200 text-white rounded hover:!bg-primary-400">
                                Zamknij
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="/panel/add_user" class="mb-6 p-4 bg-white rounded-2xl shadow-custom mx-1 dark:bg-gray-900 dark:text-white">
                <div class="mb-2">
                    <label>
                        <input type="text" name="username" placeholder="Nazwa użytkownika" class="w-full p-2 border rounded dark:bg-gray-950 dark:text-white" required>
                    </label>
                </div>
                <div class="mb-2">
                    <label>
                        <input type="text" name="password" placeholder="Hasło" class="w-full p-2 border rounded dark:bg-gray-950 dark:text-white" required>
                    </label>
                </div>
                <input type="submit" name="add_user" value="Dodaj" class="!bg-primary-200 text-white px-4 py-2 rounded hover:!bg-primary-400">

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars(SessionHelper::get('user_id')) ?>">
            </form>

            <?php if (!empty($users)): ?>
            <div class="mx-1 mb-2 rounded-2xl overflow-hidden shadow bg-white dark:bg-gray-900 dark:text-white">
                <table id="usersTable" class="w-full table-fixed border-collapse">
                    <thead class="bg-gray-200 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 border">Id</th>
                        <th class="px-4 py-2 border">Nazwa użytkownika</th>
                        <th class="px-4 py-2 border">Akcje</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($user->id) ?></td>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($user->username) ?></td>
                            <td class="px-4 py-2 border space-x-2">
                                <button type="button"
                                        class="delete-btn bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded"
                                        data-user-id="<?= htmlspecialchars($user->id) ?>">
                                    Usuń
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p>Brak ogłoszeń do wyświetlenia.</p>
            <?php endif; ?>

            <div id="confirmationModal" class="fixed inset-0 flex items-center justify-center hidden z-50">
                <div class="absolute inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm"></div>

                <div class="relative bg-white p-6 rounded shadow-lg max-w-sm w-full z-10 dark:bg-gray-800 dark:text-white">
                    <h2 class="text-xl font-semibold mb-4">Potwierdzenie usunięcia</h2>
                    <p class="mb-6">Czy na pewno chcesz usunąć tego użytkownika? Tej operacji nie można cofnąć.</p>
                    <div class="flex justify-end">
                        <button id="cancelDeleteBtn" class="mr-4 px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                            Anuluj
                        </button>
                        <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                            Usuń
                        </button>
                    </div>
                </div>

                <form method="POST" action="/panel/delete_user" class="delete-form hidden">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                    <input type="hidden" name="user_id" value="">
                </form>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const modals = {
                        confirmation: document.getElementById('confirmationModal'),
                        error: document.getElementById('errorModal')
                    };

                    const forms = {
                        delete: document.querySelector('#confirmationModal form.delete-form'),
                    };

                    const buttons = {
                        cancelDelete: document.getElementById('cancelDeleteBtn'),
                        confirmDelete: document.getElementById('confirmDeleteBtn'),
                        closeError: document.getElementById('closeErrorModal'),
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
                        forms.delete.querySelector('input[name="user_id"]').value = event.currentTarget.getAttribute('data-user-id');
                        toggleModal(modals.confirmation, true);
                    };

                    addClickEventToButtons('.delete-btn', handleDeleteButtonClick);
                    buttons.cancelDelete.addEventListener('click', () => toggleModal(modals.confirmation, false));
                    buttons.confirmDelete.addEventListener('click', () => forms.delete.submit());

                    if (modals.error) {
                        modals.error.classList.remove('hidden');
                    }

                    if (buttons.closeError) {
                        buttons.closeError.addEventListener('click', () => {
                            modals.error.classList.add('hidden');
                        });
                    }

                });
            </script>
        </main>
        <?php include('functions/footer.php'); ?>
    </body>
</html>