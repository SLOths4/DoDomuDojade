<?php
require_once __DIR__ . '/vendor/autoload.php';

use src\utilities\WeatherService;

header('Content-Type: application/json');

try {
    $weatherService = new WeatherService();
    $weatherServiceResponse = $weatherService->Weather();
    echo json_encode([
        'success' => true,
        'data' => [
            'temperature' => htmlspecialchars($weatherServiceResponse['imgw_temperature']),
            'pressure' => htmlspecialchars($weatherServiceResponse['imgw_pressure']),
            'airlyIndex' => htmlspecialchars($weatherServiceResponse['airly_index_value']),
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch weather data.'
    ]);
}