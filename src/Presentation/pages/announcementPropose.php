<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Zgłoś ogłoszenie - DoDomuDojadę</title>
    <link rel="icon" type="image/x-icon" href="/assets/resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
    <link href="/assets/styles/output.css" rel="stylesheet" type="text/css">
</head>
<body class="bg-white flex flex-col min-h-screen dark:bg-gray-800 dark:text-white py-8">
    <main class="flex-grow max-w-2xl mx-auto w-full px-4">
        <div class="mb-8">
            <h1 class="mb-2 text-4xl tracking-tight font-extrabold text-primary-400 dark:text-primary-300">
                Zgłoś ogłoszenie
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Podziel się swoją wiadomością z innymi użytkownikami DoDomuDojadę
            </p>
        </div>

        <!-- Success Message -->
        <div id="successMessage" class="hidden mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded dark:bg-green-900 dark:border-green-700 dark:text-green-200">
            <i class="fas fa-check-circle mr-2"></i>
            <span id="successText">Ogłoszenie zostało zgłoszone! Czeka na akceptację administratora.</span>
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="hidden mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded dark:bg-red-900 dark:border-red-700 dark:text-red-200">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span id="errorText">Coś poszło nie tak. Spróbuj ponownie.</span>
        </div>

        <!-- Form Card -->
        <div class="mb-3 p-4 bg-white dark:bg-gray-900 dark:text-white rounded-2xl shadow-custom mx-1">
            <form id="announceForm" method="POST" action="/public/announcement/propose" class="space-y-6">
                <input type="hidden" name="csrf_token" value="">

                <!-- Title Field -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-white mb-2">
                        <span class="text-red-500">*</span> Tytuł ogłoszenia
                    </label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="255"
                        required
                        placeholder="Np. Potrzebuję pomocy z zadaniem z matematyki"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent dark:bg-gray-950 dark:border-gray-700 dark:text-white"
                    >
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        <span id="title_counter">0</span> / 255 znaków
                    </div>
                </div>

                <!-- Content Field -->
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 dark:text-white mb-2">
                        <span class="text-red-500">*</span> Treść ogłoszenia
                    </label>
                    <textarea
                        id="content"
                        name="content"
                        maxlength="5000"
                        required
                        rows="6"
                        placeholder="Opisz szczegóły swojego ogłoszenia. Gdzie? Kiedy? Dodatkowe informacje..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent dark:bg-gray-950 dark:border-gray-700 dark:text-white resize-none"
                    ></textarea>
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        <span id="content_counter">0</span> / 5000 znaków
                    </div>
                </div>

                <!-- Expiration Date -->
                <div>
                    <label for="expires_at" class="block text-sm font-medium text-gray-700 dark:text-white mb-2">
                    Ważne do (opcjonalne)
                    </label>
                    <input
                        type="date"
                        id="expires_at"
                        name="expires_at"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent dark:bg-gray-950 dark:border-gray-700 dark:text-white"
                    >
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Jeśli nie wybierzesz daty, ogłoszenie będzie ważne przez 30 dni
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-4 pt-4">
                    <button type="submit" class="px-4 py-2 !bg-primary-200 text-white rounded hover:!bg-primary-400">
                        <i class="fas fa-paper-plane mr-2"></i> Zgłoś ogłoszenie
                    </button>
                </div>
            </form>
        </div>

        <!-- Info Box -->
        <div class="mt-8 p-4 bg-blue-50 border-l-4 border-primary-400 rounded dark:bg-blue-900 dark:border-primary-300">
            <p class="text-sm text-gray-700 dark:text-gray-300">
                <i class="fas fa-info-circle text-primary-400 dark:text-primary-300 mr-2"></i>
                <strong>Ważne:</strong> Twoje ogłoszenie będzie oczekiwać na akceptację administratora przed publikacją.
                Spróbuj być konkretny* i rzeczowy* – ogłoszenia spamowe będą odrzucone.
            </p>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('announceForm');
    const titleInput = document.getElementById('title');
    const contentInput = document.getElementById('content');
    const expiresAtInput = document.getElementById('expires_at');
    const titleCounter = document.getElementById('title_counter');
    const contentCounter = document.getElementById('content_counter');
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');

    // Character counters
    const updateCounterFactory = (input, counter) => () => {
        counter.textContent = input.value.length;
    };

            const enforceMaxLength = (field) => {
        const maxLength = field.maxLength;
        if (field.value.length > maxLength) {
            field.value = field.value.slice(0, maxLength);
        }
    };

            const initCounter = (field, counter) => {
        const updateCounter = updateCounterFactory(field, counter);

        field.addEventListener('input', () => {
            enforceMaxLength(field);
            updateCounter();
        });

                updateCounter();
            };

            initCounter(titleInput, titleCounter);
            initCounter(contentInput, contentCounter);

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            expiresAtInput.setAttribute('min', today);

            // Set default date to 30 days from now
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + 30);
            expiresAtInput.value = defaultDate.toISOString().split('T')[0];

            // Form validation and submission
            form.addEventListener('submit', (event) => {
        event.preventDefault();

        // Reset messages
        successMessage.classList.add('hidden');
        errorMessage.classList.add('hidden');

        // Validation
        if (!titleInput.value.trim()) {
            showError('Tytuł jest wymagany');
            return;
        }

        if (!contentInput.value.trim()) {
            showError('Treść ogłoszenia jest wymagana');
            return;
        }

        if (titleInput.value.length < 5) {
            showError('Tytuł musi mieć co najmniej 5 znaków');
            return;
        }

        if (contentInput.value.length < 10) {
            showError('Treść ogłoszenia musi mieć co najmniej 10 znaków');
            return;
        }

        // Submit form
        form.submit();
    });

            const showError = (message) => {
        document.getElementById('errorText').textContent = message;
        errorMessage.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            // Show success message if present in URL params or session
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                successMessage.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    </script>
</body>
</html>