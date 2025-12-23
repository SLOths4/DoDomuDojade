<?php
return [
    'error.user_not_found' => 'User not found',

    'csrf' => [
        'invalid' => 'Invalid csrf token'
    ],

    // Auth
    'auth' => [
        'invalid_credentials' => 'Invalid credentials',
        'error_unknown' => 'Unknown error occurred',
        'empty_credentials' => 'Credentials cannot be empty',
        'no_user_logged_in' => 'No user logged in',
        'user_not_found' => 'No user found for provided credentials',
        'unauthorized' => 'User not authorized',
    ],

    // view
    'view' => [
        'user_not_authenticated' => 'User is not authenticated',
        'load_failed' => 'Failed to load page',
        'missing_data' => 'Required data is missing',
    ],

    // User
    'user.not_found' => 'User not found',
    'user.already_exists' => 'User already exists',

    // Announcement
    'announcement' => [
        'invalid_id' => 'Invalid announcement ID',
        'empty_title' => 'Announcement title cannot be empty',
        'empty_text' => 'Announcement text cannot be empty',
        'invalid_valid_until' => 'Expiry date must be in the future',
        'not_found' => 'Announcement not found',
        'create_failed' => 'Failed to create announcement',
        'delete_failed' => 'Failed to delete announcement',
        'update_failed' => 'Failed to update announcement',
        'no_changes' => 'No changes were made',
        'unauthorized' => 'You do not have permission for this action',
        'cannot_edit_others' => 'You can only edit your own announcements',
        'already_approved' => 'This announcement is already approved',
        'already_rejected' => 'This announcement is already rejected',
        'created_successfully' => 'Announcement has been created',
        'deleted_successfully' => 'Announcement has been deleted',
        'updated_successfully' => 'Announcement has been updated',
        'approved_successfully' => 'Announcement has been approved',
        'rejected_successfully' => 'Announcement has been rejected',
        'proposed_successfully' => 'Announcement has been submitted for approval',
    ],

    'countdown' => [
        'invalid_id' => 'Invalid countdown ID',
        'empty_fields' => 'All fields are required',
        'invalid_date_format' => 'Invalid date format',
        'not_found' => 'Countdown not found',
        'no_changes' => 'No changes were made',
        'create_failed' => 'Failed to create countdown',
        'update_failed' => 'Failed to update countdown',
        'delete_failed' => 'Failed to delete countdown',
        'created_successfully' => 'Countdown has been created',
        'updated_successfully' => 'Countdown has been updated',
        'deleted_successfully' => 'Countdown has been deleted',
    ],

    'module' => [
        'invalid_id' => 'Invalid module ID',
        'not_found' => 'Module not found',
        'invalid_time_format' => 'Invalid time format',
        'update_failed' => 'Failed to update module',
        'toggle_failed' => 'Failed to toggle module',
        'toggled_successfully' => 'Module status has been changed',
        'updated_successfully' => 'Module has been updated',
    ],

    'user' => [
        'invalid_id' => 'Invalid user ID',
        'empty_fields' => 'Username and password are required',
        'not_found' => 'User not found',
        'username_taken' => 'Username is already taken',
        'unauthorized' => 'You do not have permission for this action',
        'cannot_delete_self' => 'You cannot delete your own account',
        'create_failed' => 'Failed to create user',
        'delete_failed' => 'Failed to delete user',
        'created_successfully' => 'User has been created',
        'deleted_successfully' => 'User has been deleted',
    ],

    'display' => [
        'module_not_visible' => 'Module is not available',
        'fetch_departures_failed' => 'Failed to fetch departure data',
        'fetch_announcements_failed' => 'Failed to fetch announcements',
        'fetch_countdown_failed' => 'Failed to fetch countdown',
        'fetch_weather_failed' => 'Failed to fetch weather data',
        'fetch_quote_failed' => 'Failed to fetch quote',
        'fetch_word_failed' => 'Failed to fetch word of the day',
    ],
];