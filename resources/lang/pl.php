<?php
return [
    'module_name' => [
        'announcement' => 'ogłoszenia',
        'calendar' => 'kalendarz',
        'countdown' => 'odliczanie',
        'tram' => 'tramwaje',
        'weather' => 'pogoda',
        'quote' => 'cytat dnia',
        'word' => 'słowo dnia',
    ],

    'validation' => [
        'csrf' => [
            'invalid' => 'Nieprawidłowy token csrf',
            'missing' => 'Brak tokenu csrf',
        ],
        'failed' => "nieudana weryfikacja danych",
    ],

    'auth' => [
        'invalid_credentials' => 'Nieprawidłowe dane logowania',
        'error_unknown' => 'Wystąpił nieznany błąd',
        'empty_credentials' => 'Dane logowania nie mogą być puste',
        'no_user_logged_in' => 'Brak zalogowanego użytkownika',
        'user_not_found' => 'Nie znaleziono użytkownika o podanych danych',
        'unauthorized' => 'Niedozwolona akcja dla użytkownika',
    ],

    // view
    'view' => [
        'user_not_authenticated' => 'Użytkownik nie jest zalogowany',
        'load_failed' => 'Nie udało się załadować strony',
        'missing_data' => 'Brakuje wymaganych danych',
    ],

    // announcement
    'announcement' => [
        // Validation
        'invalid_id' => 'Nieprawidłowy identyfikator ogłoszenia',
        'empty_title' => 'Tytuł ogłoszenia nie może być pusty',
        'empty_text' => 'Treść ogłoszenia nie może być pusta',
        'invalid_valid_until' => 'Data ważności musi być w przyszłości',
        'expiration_to_far_in_future' => 'Data ważności zbytnio wybiega w przyszłość',
        'invalid_text_length' => 'Treść ogłoszenia musi mieć co najmniej :min_text_length znaków',
        'invalid_title_length' => 'Treść ogłoszenia może mieć maksymalnie :max_text_length znaków',
        'title_too_short' => 'Tytuł ogłoszenia musi mieć co najmniej :min_title_length znaków',
        'title_too_long' => 'Tytuł ogłoszenia może mieć maksymalnie :max_title_length znaków',
        'invalid_status' => 'Nieprawidłowy status ogłoszenia',
        'expiration_in_the_past' => 'Data ważności nie może być w przeszłości',

        // Operations
        'not_found' => 'Ogłoszenie nie zostało znalezione',
        'create_failed' => 'Nie udało się dodać ogłoszenia',
        'delete_failed' => 'Nie udało się usunąć ogłoszenia',
        'update_failed' => 'Nie udało się zaktualizować ogłoszenia',
        'status_update_failed' => 'Nie udało się zaktualizować status',
        'no_changes' => 'Nie wprowadzono żadnych zmian',

        // Authorization
        'unauthorized' => 'Brak uprawnień do tej akcji',
        'cannot_edit_others' => 'Możesz edytować tylko własne ogłoszenia',

        // Status
        'already_approved' => 'Ogłoszenie jest już zatwierdzone',
        'already_rejected' => 'Ogłoszenie jest już odrzucone',

        // Success messages
        'created_successfully' => 'Ogłoszenie zostało dodane',
        'deleted_successfully' => 'Ogłoszenie zostało usunięte',
        'updated_successfully' => 'Ogłoszenie zostało zaktualizowane',
        'approved_successfully' => 'Ogłoszenie zostało zatwierdzone',
        'rejected_successfully' => 'Ogłoszenie zostało odrzucone',
        'proposed_successfully' => 'Ogłoszenie zostało zgłoszone do akceptacji',
    ],

    'countdown' => [
        'invalid_id' => 'Nieprawidłowy identyfikator odliczania',
        'empty_fields' => 'Wszystkie pola są wymagane',
        'invalid_date_format' => 'Nieprawidłowy format daty',
        'not_found' => 'Odliczanie nie zostało znalezione',
        'no_changes' => 'Nie wprowadzono żadnych zmian',
        'create_failed' => 'Nie udało się utworzyć odliczania',
        'update_failed' => 'Nie udało się zaktualizować odliczania',
        'delete_failed' => 'Nie udało się usunąć odliczania',
        'fetch_failed' => 'Nie udało się pobrać odliczania/odliczań',
        'created_successfully' => 'Odliczanie zostało utworzone',
        'updated_successfully' => 'Odliczanie zostało zaktualizowane',
        'deleted_successfully' => 'Odliczanie zostało usunięte',
        'title_too_short' => 'Tytuł odliczania musi mieć co najmniej :min_title_length znaków',
        'title_too_long' => 'Tytuł odliczania może mieć maksymalnie :max_title_length znaków',
        'count_to_in_the_past' => 'Data odliczania nie może być w przeszłości'
    ],

    'module' => [
        'invalid_id' => 'Nieprawidłowy identyfikator modułu',
        'not_found' => 'Moduł nie został znaleziony',
        'invalid_time_format' => 'Nieprawidłowy format czasu',
        'update_failed' => 'Nie udało się zaktualizować modułu',
        'toggle_failed' => 'Nie udało się zmienić statusu modułu',
        'toggled_successfully' => 'Status modułu został zmieniony',
        'updated_successfully' => 'Moduł został zaktualizowany',
    ],

    'user' => [
        'invalid_id' => 'Nieprawidłowy identyfikator użytkownika',
        'empty_fields' => 'Nazwa użytkownika i hasło są wymagane',
        'not_found' => 'Użytkownik nie został znaleziony',
        'username_taken' => 'Nazwa użytkownika jest już zajęta',
        'unauthorized' => 'Brak uprawnień do tej akcji',
        'cannot_delete_self' => 'Nie możesz usunąć własnego konta',
        'create_failed' => 'Nie udało się utworzyć użytkownika',
        'delete_failed' => 'Nie udało się usunąć użytkownika',
        'created_successfully' => 'Użytkownik został utworzony',
        'deleted_successfully' => 'Użytkownik został usunięty',
        'updated_successfully' => 'Użytkownik został zaktualizowany',
        'password_changed_successfully' => 'Hasło zostało zmienione',
    ],

    'display' => [
        'module_not_visible' => 'Moduł nie jest dostępny',
        'fetch_departures_failed' => 'Nie udało się pobrać danych o odjazdach',
        'fetch_announcements_failed' => 'Nie udało się pobrać ogłoszeń',
        'fetch_countdown_failed' => 'Nie udało się pobrać odliczania',
        'fetch_weather_failed' => 'Nie udało się pobrać danych pogodowych',
        'fetch_quote_failed' => 'Nie udało się pobrać cytatu',
        'fetch_word_failed' => 'Nie udało się pobrać słowa dnia',
    ],
];
