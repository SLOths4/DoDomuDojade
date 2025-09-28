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
        <link rel="icon" type="image/x-icon" href="/assets/resources/favicon.ico">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/flowbite@1.6.5/dist/flowbite.min.css" />
        <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
        <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="//unpkg.com/alpinejs" defer></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://unpkg.com/flowbite@1.6.5/dist/flowbite.min.js"></script>
        <link href="/assets/styles/dist/output.css" rel="stylesheet" type="text/css">
    </head>
    <body class="flex flex-col min-h-screen dark:bg-gray-800 dark:text-white">
        <?php include('functions/navbar.php'); ?>
        <main class="flex-grow">
            <?php if (!empty($modules)): ?>
                <table id="modulesTable" class="min-w-full bg-white border dark:bg-gray-900 dark:text-white">
                    <thead class="bg-gray-200 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 border">Nazwa modułu</th>
                        <th class="px-4 py-2 border" data-type="time">Godzina rozpoczęcia</th>
                        <th class="px-4 py-2 border" data-type="time">Godzina zakończenia</th>
                        <th class="px-4 py-2 border" data-type="time">Stan</th>
                        <th class="px-4 py-2 border" data-type="time">Akcje</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($modules as $module): ?>
                        <tr>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($module['module_name']) ?></td>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($module['start_time']) ?></td>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($module['end_time']) ?></td>
                            <td class="px-4 py-2 border"><?= $module['is_active'] ? "Włączony" : "Wyłączony" ?></td>
                            <td class="px-4 py-2 border space-x-2">
                                <button type="button"
                                        class="edit-btn bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-2 rounded"
                                        data-module-id="<?= htmlspecialchars($module['id']) ?>"
                                        data-start-time="<?= htmlspecialchars($module['start_time']) ?>"
                                        data-end-time="<?= htmlspecialchars($module['end_time']) ?>"
                                        data-is-active="<?= htmlspecialchars((string)(int)$module['is_active']) ?>"
                                >
                                    Edytuj
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Brak modułów do wyświetlenia.</p>
            <?php endif; ?>

            <div id="editionModal" class="fixed inset-0 flex items-center justify-center hidden z-50">
                <div class="absolute inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm"></div>

                <div class="relative bg-white p-6 rounded shadow-lg max-w-md w-full z-10 dark:bg-gray-800 dark:text-white">
                    <h2 class="text-xl font-semibold mb-4">Edytuj godziny wyświetlania modułu</h2>
                    <form method="POST" action="/panel/edit_module" id="editModuleForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
                        <input type="hidden" id="edit_module_id" name="module_id">

                        <div class="mb-4">
                            <label for="edit_start_time" class="block text-sm font-medium text-gray-700 dark:text-white">Od</label>
                            <input type="datetime" id="edit_start_time" name="start_time" class="w-full p-2 border rounded dark:bg-gray-950 dark:text-white">
                        </div>

                        <div class="mb-4">
                            <label for="edit_end_time" class="block text-sm font-medium text-gray-700 dark:text-white">Do</label>
                            <input type="datetime" id="edit_end_time" name="end_time" class="w-full p-2 border rounded dark:bg-gray-950 dark:text-white">
                        </div>

                        <div class="mb-4">
                            <label for="edit_is_active" class="block text-sm font-medium text-gray-700 dark:text-white">Aktywny</label>
                            <input type="checkbox" id="edit_is_active" name="is_active" class="p-2 border rounded dark:bg-gray-950 dark:text-white">
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
                    const modals = {
                        edition: document.getElementById('editionModal')
                    };

                    const forms = {
                        edit: document.getElementById('editModuleForm')
                    };

                    const buttons = {
                        cancelEdit: document.getElementById('cancelEditBtn'),
                    };

                    const toggleModal = (modal, show) => {
                        modal.classList.toggle('hidden', !show);
                    };

                    const handleEditButtonClick = (event) => {
                        event.preventDefault();

                        const btn = event.currentTarget;

                        const moduleId = btn.getAttribute('data-module-id');
                        const startTime = btn.getAttribute('data-start-time');
                        const endTime = btn.getAttribute('data-end-time');
                        const isActive = btn.getAttribute('data-is-active') === '1';

                        forms.edit.querySelector('#edit_module_id').value = moduleId;
                        forms.edit.querySelector('#edit_start_time').value = startTime;
                        forms.edit.querySelector('#edit_end_time').value = endTime;
                        forms.edit.querySelector('#edit_is_active').checked = isActive;

                        toggleModal(modals.edition, true);
                    };

                    document.querySelectorAll('.edit-btn').forEach(btn => {
                        btn.addEventListener('click', handleEditButtonClick);
                    });

                    buttons.cancelEdit.addEventListener('click', () => toggleModal(modals.edition, false));
                });
            </script>
        </main>
        <?php include('functions/footer.php'); ?>
    </body>
</html>