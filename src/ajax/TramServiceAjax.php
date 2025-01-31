<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Monolog\Level;
use src\utilities\TramService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

header('Content-Type: application/json');

$config = require '../config.php';

// Logger i ustawienie połączenia
$logger = new Logger('TramHandler');
$logger->pushHandler(new StreamHandler(__DIR__ . '/log/tram.log', Level::Debug));

try {
    // Tworzenie usługi dla tramwajów
    $tramService = new TramService($logger, "https://www.peka.poznan.pl/vm/method.vm");

    // Pobieranie danych odjazdów tramwajów dla przystanku (np. "AWF73")
    $stopId = $_GET['stop'] ?? 'AWF73'; // Domyślnie przystanek "AWF73"
    $departures = $tramService->getTimes($stopId);

    if (isset($departures['success']['times']) && is_array($departures['success']['times'])) {
        $response = [];
        foreach ($departures['success']['times'] as $departure) {
            $response[] = [
                'line' => htmlspecialchars($departure['line']),
                'minutes' => htmlspecialchars($departure['minutes']),
                'direction' => htmlspecialchars($departure['direction']),
            ];
        }
        echo json_encode(['success' => true, 'data' => $response]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Brak danych o odjazdach.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd w trakcie przetwarzania danych tramwajowych: ' . $e->getMessage()]);
}