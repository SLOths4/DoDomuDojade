<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Panel | DoDomuDojadę</title>
    <link rel="icon" type="image/x-icon" href="/assets/resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <link href="/assets/styles/output.css" rel="stylesheet" type="text/css">
</head>
<body class="flex flex-col min-h-screen bg-primary-200 dark:bg-primary-400 dark:text-white">
<?php if ($navbar): ?>
    <?php include $VIEWS_PATH . 'layouts/navbar.php'; ?>
<?php endif; ?>
<main class="flex-grow">
    <?php if ($error): ?>
        <div class="mb-4 p-2 bg-red-100 text-red-700 rounded">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-4 p-2 bg-green-100 text-green-700 rounded">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <!-- SEKCJA 1: Formularz dodawania ogłoszenia -->
    <form method="POST" action="/panel/add_announcement" class="mb-3 p-4 bg-white dark:bg-gray-900 dark:text-white rounded-2xl shadow-custom mx-1">
        <div class="mb-2">
            <label>
                <input type="text" id="add_title" name="title" placeholder="Tytuł" class="w-full p-2 border rounded dark:bg-gray-950 dark:text-white" maxlength="50" required>
                <span id="add_title_counter" class="text-sm text-gray-600 dark:text-gray-400">0 / 50 znaków</span>
            </label>
        </div>
        <div class="mb-2">
            <label>
                <input type="text" id="add_text" name="text" placeholder="Tekst" class="w-full p-2 border rounded dark:bg-gray-950 dark:text-white" maxlength="500" required>
                <span id="add_text_counter" class="text-sm text-gray-600 dark:text-gray-400">0 / 500 znaków</span>
            </label>
        </div>
        <div class="mb-2">
            <label>
                <input type="date" name="valid_until" placeholder="Ważne do" class="w-full p-2 border rounded dark:bg-gray-950 dark:text-white" required>
            </label>
        </div>
        <div class="flex items-center justify-between">
            <input type="submit" name="add_announcement" value="Dodaj" class="!bg-primary-200 dark:text-white px-4 py-2 rounded hover:!bg-primary-400">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        </div>
    </form>

    <!-- SEKCJA 2: Tabela ogłoszeń do akceptacji (PENDING) -->
    <?php if (!empty($pendingAnnouncements)): ?>
        <div class="mx-1 mb-2 rounded-2xl overflow-hidden shadow bg-white dark:bg-gray-900 dark:text-white">
            <h2 class="text-2xl font-bold p-4 bg-yellow-50 dark:bg-yellow-900 border-b dark:border-yellow-700">
                <i class="fa-solid fa-clock mr-2"></i>Ogłoszenia oczekujące na akceptację (<?= count($pendingAnnouncements) ?>)
            </h2>
            <table id="pendingAnnouncementsTable" class="w-full table-fixed border-collapse">
                <thead class="bg-gray-200 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 border">Tytuł</th>
                    <th class="px-4 py-2 border">Autor</th>
                    <th class="px-4 py-2 border">Data publikacji</th>
                    <th class="px-4 py-2 border">Ważne do</th>
                    <th class="px-4 py-2 border">Treść</th>
                    <th class="px-4 py-2 border">Akcje</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pendingAnnouncements as $announcement): ?>
                    <tr>
                        <td class="break-words whitespace-normal px-4 py-2 border"><?= e($announcement->title) ?></td>
                        <td class="break-words whitespace-normal px-4 py-2 border"><?= e($usernames[$announcement->userId] ?? "Nieznany użytkownik") ?></td>
                        <td class="break-words whitespace-normal px-4 py-2 border"><?= e($announcement->createdAt) ?></td>
                        <td class="break-words whitespace-normal px-4 py-2 border"><?= e($announcement->validUntil) ?></td>
                        <td class="break-words whitespace-normal px-4 py-2 border"><?= e($announcement->text) ?></td>
                        <td class="break-words whitespace-normal px-4 py-2 border space-x-2">
                            <form method="POST" action="/panel/approve_announcement" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                <input type="hidden" name="announcement_id" value="<?= e($announcement->id) ?>">
                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-2 rounded text-sm">
                                    <i class="fa-solid fa-check mr-1"></i>Zaakceptuj
                                </button>
                            </form>
                            <form method="POST" action="/panel/reject_announcement" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="announcement_id" value="<?= e($announcement->id) ?>">
                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded text-sm">
                                    <i class="fa-solid fa-xmark mr-1"></i>Odrzuć
                                </button>
                            </form>
                            <button type="button"
                                    class="edit-btn bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-2 rounded text-sm"
                                    data-announcement-id="<?= e($announcement->id) ?>"
                                    data-title="<?= e($announcement->title) ?>"
                                    data-text="<?= e($announcement->text) ?>"
                                    data-valid-until="<?= e($announcement->validUntil) ?>"
                                    data-status="<?= match($announcement->status) {
                                        'APPROVED' => '1', 'REJECTED' => '2', default => '0' } ?>">
                                <i class="fa-solid fa-edit mr-1"></i>Edytuj
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- SEKCJA 3: Tabela wszystkich ogłoszeń (APPROVED + REJECTED) -->
    <?php if (!empty($announcements)): ?>
        <div class="mx-1 mb-2 rounded-2xl overflow-hidden shadow bg-white dark:bg-gray-900 dark:text-white">
            <h2 class="text-2xl font-bold p-4 bg-gray-100 dark:bg-gray-800 border-b dark:border-gray-700">
                <i class="fa-solid fa-list mr-2"></i>Wszystkie ogłoszenia
            </h2>
            <table id="announcementsTable" class="w-full table-fixed border-collapse">
                <thead class="bg-gray-200 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 border">Tytuł</th>
                    <th class="px-4 py-2 border">Autor</th>
                    <th class="px-4 py-2 border">Data publikacji</th>
                    <th class="px-4 py-2 border" data-type="date" data-format="YYYY-MM-DD">Ważne do</th>
                    <th class="px-4 py-2 border">Status</th>
                    <th class="px-4 py-2 border">Decyzja</th>
                    <th class="px-4 py-2 border">Treść</th>
                    <th class="px-4 py-2 border">Akcje</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($announcements as $announcement): ?>
                    <tr>
                        <td class="break-words whitespace-normal px-4 py-2 border"><?= e($announcement->title) ?></td>
                        <td class="break-words whitespace-normal px-4 py-2 border"><?= e($usernames[$announcement->userId] ?? "Nieznany użytkownik") ?></td>
                        <td class="break-words whitespace-normal px-4 py-2 border"><?= e($announcement->createdAt) ?></td>
                        <td class="break-words whitespace-normal px-4 py-2 border"><?= e($announcement->validUntil) ?></td>
                        <td class="break-words whitespace-normal px-4 py-2 border">
                            <?php
                            $statusClass = match($announcement->status) {
                                'APPROVED' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                                'REJECTED' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                                'PENDING' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'
                            };
                            ?>
                            <span class="px-2 py-1 rounded text-xs font-semibold <?= $statusClass ?>">
                                <?= e($announcement->status) ?>
                            </span>
                        </td>
                        <td class="break-words whitespace-normal px-4 py-2 border text-xs">
                            <?php if ($announcement->decidedAt): ?>
                                <?= e($announcement->decidedAt) ?><br>
                                przez: <?= e($usernames[$announcement->decidedBy] ?? "Admin") ?>
                            <?php else: ?>
                                <span class="text-gray-400 dark:text-gray-500">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="break-words whitespace-normal px-4 py-2 border"><?= e($announcement->text) ?></td>
                        <td class="break-words whitespace-normal px-4 py-2 border space-x-2">
                            <button type="button"
                                    class="delete-btn bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded text-sm"
                                    data-announcement-id="<?= e($announcement->id) ?>">
                                Usuń
                            </button>
                            <button type="button"
                                    class="edit-btn bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-2 rounded text-sm"
                                    data-announcement-id="<?= e($announcement->id) ?>"
                                    data-title="<?= e($announcement->title) ?>"
                                    data-text="<?= e($announcement->text) ?>"
                                    data-valid-until="<?= e($announcement->validUntil) ?>"
                                    data-status="<?= match($announcement->status) {
                                        'APPROVED' => '1', 'REJECTED' => '2', default => '0' } ?>">
                                Edytuj
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="bg-amber-100 mx-3 text-xl border border-yellow-500 rounded-lg flex items-center space-x-2">
            <i class="fa-solid fa-triangle-exclamation text-yellow-500 p-2.5" aria-hidden="true"></i>
            <p class="text-yellow-500 text-sm font-medium">Brak ogłoszeń do wyświetlania</p>
        </div>
    <?php endif; ?>

    <!-- Modals -->
    <div id="confirmationModal" class="fixed inset-0 flex items-center justify-center hidden z-50">
        <div class="absolute inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm"></div>

        <div class="relative bg-white p-6 rounded shadow-lg max-w-sm w-full z-10 dark:bg-gray-800 dark:text-white">
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
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="announcement_id" value="">
        </form>
    </div>

    <div id="editionModal" class="fixed inset-0 flex items-center justify-center hidden z-50">
        <div class="absolute inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm"></div>

        <div class="relative bg-white p-6 rounded shadow-lg max-w-md w-full z-10 dark:bg-gray-800 dark:text-white">
            <h2 class="text-xl font-semibold mb-4">Edytuj ogłoszenie</h2>
            <form method="POST" action="/panel/edit_announcement" id="editAnnouncementForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" id="edit_announcement_id" name="announcement_id">

                <div class="mb-4">
                    <label for="edit_title" class="block text-sm font-medium text-gray-700 dark:text-white mb-2">Tytuł</label>
                    <input type="text" id="edit_title" name="title" maxlength="255" class="w-full p-2 border rounded dark:bg-gray-950 dark:border-gray-700 dark:text-white">
                    <span id="edit_title_char_counter" class="text-sm text-gray-600 dark:text-gray-400">0 / 255 znaków</span>
                </div>

                <div class="mb-4">
                    <label for="edit_text" class="block text-sm font-medium text-gray-700 dark:text-white mb-2">Treść</label>
                    <textarea id="edit_text" name="text" maxlength="5000" rows="5" class="w-full p-2 border rounded dark:bg-gray-950 dark:border-gray-700 dark:text-white resize-none"></textarea>
                    <span id="edit_text_char_counter" class="text-sm text-gray-600 dark:text-gray-400">0 / 5000 znaków</span>
                </div>

                <div class="mb-4">
                    <label for="edit_valid_until" class="block text-sm font-medium text-gray-700 dark:text-white mb-2">Ważne do</label>
                    <input type="date" id="edit_valid_until" name="valid_until" class="w-full p-2 border rounded dark:bg-gray-950 dark:border-gray-700 dark:text-white">
                </div>

                <div class="mb-4">
                    <label for="edit_status" class="block text-sm font-medium text-gray-700 dark:text-white mb-2">Status</label>
                    <select id="edit_status" name="status" class="w-full p-2 border rounded dark:bg-gray-950 dark:border-gray-700 dark:text-white">
                        <option value="PENDING">Oczekuje zatwierdzenia</option>
                        <option value="APPROVED">Zatwierdzone</option>
                        <option value="REJECTED">Odrzucone</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" id="cancelEditBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600">
                        Anuluj
                    </button>
                    <button type="submit" class="px-4 py-2 !bg-primary-200 text-white rounded hover:!bg-primary-400">
                        Zapisz zmiany
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const textarea = document.getElementById("edit_text");
            const titleInput = document.getElementById("edit_title");
            const textCounter = document.getElementById("edit_text_char_counter");
            const titleCounter = document.getElementById("edit_title_char_counter");
            const addTitleInput = document.getElementById("add_title");
            const addTextInput = document.getElementById("add_text");
            const addTitleCounter = document.getElementById("add_title_counter");
            const addTextCounter = document.getElementById("add_text_counter");

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
                cancelEdit: document.getElementById('cancelEditBtn')
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
                forms.delete.querySelector('input[name="announcement_id"]').value = event.currentTarget.getAttribute('data-announcement-id');
                toggleModal(modals.confirmation, true);
            };

            const handleEditButtonClick = (event) => {
                event.preventDefault();
                const btn = event.currentTarget;

                forms.edit.querySelector('#edit_announcement_id').value = btn.getAttribute('data-announcement-id');
                forms.edit.querySelector('#edit_title').value = btn.getAttribute('data-title');
                forms.edit.querySelector('#edit_text').value = btn.getAttribute('data-text');
                forms.edit.querySelector('#edit_valid_until').value = btn.getAttribute('data-valid-until');
                forms.edit.querySelector('#edit_status').value = btn.getAttribute('data-status') || '0';

                initCounter(titleInput, titleCounter);
                initCounter(textarea, textCounter);

                toggleModal(modals.edition, true);
            };

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
                field.removeEventListener("input", updateCounter);
                field.addEventListener("input", () => {
                    enforceMaxLength(field);
                    updateCounter();
                });

                enforceMaxLength(field);
                updateCounter();
            };

            if (addTitleInput && addTitleCounter) {
                initCounter(addTitleInput, addTitleCounter);
            }

            if (addTextInput && addTextCounter) {
                initCounter(addTextInput, addTextCounter);
            }

            addClickEventToButtons('.delete-btn', handleDeleteButtonClick);
            buttons.cancelDelete.addEventListener('click', () => toggleModal(modals.confirmation, false));
            buttons.confirmDelete.addEventListener('click', () => forms.delete.submit());

            addClickEventToButtons('.edit-btn', handleEditButtonClick);
            buttons.cancelEdit.addEventListener('click', () => toggleModal(modals.edition, false));
            forms.edit.addEventListener('submit', (e) => {
                e.preventDefault();
                forms.edit.submit();
            });
        });
    </script>
</main>
<?php if ($footer): ?>
    <?php include $VIEWS_PATH . 'layouts/footer.php'; ?>
<?php endif; ?>
</body>
</html>
