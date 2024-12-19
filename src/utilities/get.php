<?php

function get($url) {
    // Initialize cURL
    $ch = curl_init();

    // Set options
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => true, // Automatically handle HTTP errors
    ]);

    // Execute request
    $response = curl_exec($ch);

    // Check for errors
    if ($response === false) {
        $error_message = 'cURL error: ' . curl_error($ch);
        curl_close($ch);
        throw new Exception($error_message);
    }

    // Close cURL
    curl_close($ch);

    // Decode JSON response
    $data = json_decode($response, true);

    // Check for JSON errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON decode error: ' . json_last_error_msg());
    }

    // Return the data
    return $data;
}
?>
