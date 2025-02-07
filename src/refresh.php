<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use src\utilities\UserService;
use src\utilities\TramService;
use src\utilities\WeatherService;
use src\utilities\AnnouncementService;
use src\utilities\CalendarService;
use Monolog\Logger;
use Dotenv\Dotenv;

$config = require './config.php';

$logger = new Monolog\Logger('AppHandler');
$logger->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . '/log/app.log', Monolog\Level::Debug));

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$ztmURL = $_ENV['DB_HOST'];

$pdo = new PDO($config['Database']['db_host']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$ztmURL = $config["API"][1]["url"];
$icalURL = $config['Calendar'][0]['url'];

$function = $_GET['function'] ?? 'default';

switch ($function) {
    case 'tramData':
        echo json_encode(getTramData($logger, $ztmURL));
        break;
    case 'announcementsData':
        echo json_encode(getAnnouncementsData($logger, $pdo));
        break;
    case 'weatherData':
        echo json_encode(getWeatherData($logger));
        break;
    case 'calendarData':
        echo json_encode(getCalendarData($logger, $icalURL));
        break;
    default:
        echo json_encode(['error' => 'Nieznana funkcja']);
        break;
}

function getTramData(Logger $logger, string $ztmURL): false|string
{
    try {
        $logger->info('Rozpoczęto pobieranie danych tramwajowych.');

        // Zdefiniowanie listy przystanków
        $stopsIdS = ['AWF73', 'AWF41', 'AWF42', 'AWF02', 'AWF01', 'AWF03']; // Lista ID przystanków
        $departures = [];

        // Tworzenie instancji TramService
        $tramService = new TramService($logger, $ztmURL);
        $logger->debug('Utworzono instancję TramService.');

        foreach ($stopsIdS as $stopId) {
            // Pobieranie danych dla każdego przystanku
            $stopDepartures = $tramService->getTimes($stopId);

            if (isset($stopDepartures['success']['times']) && is_array($stopDepartures['success']['times'])) {
                foreach ($stopDepartures['success']['times'] as $departure) {
                    $departures[] = [
                        'stopId' => $stopId,
                        'line' => htmlspecialchars($departure['line']),
                        'minutes' => (int)htmlspecialchars($departure['minutes']),
                        'direction' => htmlspecialchars($departure['direction']),
                    ];
                }
            } else {
                $logger->warning("Brak dostępnych danych o odjazdach dla przystanku: $stopId.");
            }
        }

        usort($departures, function ($a, $b) {
            return $a['minutes'] <=> $b['minutes'];
        });

        if (!empty($departures)) {
            $logger->debug('Pomyślnie pobrano dane tramwajowe.');
            return json_encode(['success' => true, 'data' => $departures]);
        } else {
            $logger->warning('Brak danych o odjazdach dla wszystkich przystanków.');
            return json_encode(['success' => false, 'message' => 'Brak danych o odjazdach dla wybranych przystanków.']);
        }
    } catch (Exception $e) {
        $logger->error('Błąd podczas przetwarzania danych tramwajowych: ' . $e->getMessage());
        return json_encode(['success' => false, 'message' => 'Błąd w trakcie przetwarzania danych tramwajowych: ' . $e->getMessage()]);
    }
}

function getAnnouncementsData(Logger $logger, PDO $pdo): false|string
{
    try {
        $logger->info('Rozpoczęto pobieranie danych ogłoszeń.');
        $announcementService = new AnnouncementService($logger, $pdo);
        $logger->debug('Utworzono instancję AnnouncementService.');
        $userService = new UserService($logger, $pdo);
        $logger->debug('Utworzono instancję UserService.');

        $announcements = $announcementService->getValidAnnouncements();
        $logger->info('Pobrano listę ogłoszeń z bazy danych.');

        $response = [];

        foreach ($announcements as $announcement) {
            try {
                $user = $userService->getUserById($announcement['user_id']);
                $author = $user['username'] ?? 'Nieznany użytkownik';
            } catch (Exception $e) {
                $author = 'Nieznany użytkownik';
                $logger->warning("Nie udało się pobrać danych użytkownika o ID: {$announcement['user_id']}. Powód: " . $e->getMessage());
            }

            $response[] = [
                'title' => htmlspecialchars($announcement['title']),
                'author' => $author,
                'date' => htmlspecialchars($announcement['date']),
                'validUntil' => htmlspecialchars($announcement['valid_until']),
                'text' => htmlspecialchars($announcement['text']),
            ];
        }

        $logger->info('Pomyślnie przetworzono dane ogłoszeń.');
        return json_encode([
            'success' => true,
            'data' => $response
        ]);
    } catch (Exception $e) {
        $logger->error('Błąd podczas pobierania ogłoszeń: ' . $e->getMessage());
        return json_encode([
            'success' => false,
            'message' => 'Error fetching announcements' . $e->getMessage()
        ]);
    }
}

function getWeatherData(Logger $logger): false|string
{
    try {
        $logger->info('Rozpoczęto przetwarzanie danych pogodowych.');

        $weatherService = new WeatherService($logger);
        $logger->debug('Utworzono instancję WeatherService.');
        $weatherServiceResponse = $weatherService->Weather();
        if (empty($weatherServiceResponse)) {
            $logger->warning('Brak danych pogodowych.');
            return json_encode([
                'success' => false,
                'message' => 'Brak danych pogodowych.'
            ]);
        }
        $logger->info('Pomyślnie pobrano dane pogodowe.');

        return json_encode([
            'success' => true,
            'data' => [
                'temperature' => htmlspecialchars($weatherServiceResponse['imgw_temperature'] ?? 'Brak danych'),
                'pressure' => htmlspecialchars($weatherServiceResponse['imgw_pressure'] ?? 'Brak danych'),
                'airlyIndex' => $weatherServiceResponse['airly_index_value'] !== null
                    ? htmlspecialchars($weatherServiceResponse['airly_index_value'])
                    : 'Brak danych'
            ]
        ]);
    } catch (Exception $e) {
        $logger->error('Błąd podczas pobierania danych pogodowych: ' . $e->getMessage());
        return json_encode([
            'success' => false,
            'message' => 'Błąd pobierania danych pogodowych: ' . $e->getMessage()
        ]);
    }
}
function getCalendarData(Logger $logger, string $icalURL): false|string
{
    try {
        $logger->info('Rozpoczęto pobieranie wydarzeń.');

        // Tworzenie usługi dla calendarwajów
        $calendarService = new CalendarService($logger, $icalURL);
        $calendarServiceResponse = $calendarService->get_events();
        $logger->debug('Utworzono instancję calendarService.');

        if (!empty($calendarServiceResponse)) {
            $response = [];
            foreach ($calendarServiceResponse as $event) {
                $response[] = [
                    'summary' => htmlspecialchars($event['summary'] ?? ''),
                    'start' => htmlspecialchars($event['start']),
                    'end' => htmlspecialchars($event['end']),
                    'description' => htmlspecialchars($event['description'] ?? ''),
                ];
            }
            $logger->debug('Pomyślnie pobrano dane wydarzenia.');
            return json_encode(['success' => true, 'data' => $response]);
        } else {
            $logger->warning('Brak dostępnych danych o wydarzeniach.');
            return json_encode(['success' => false, 'message' => 'Brak danych o wydarzeniach.']);
        }
    } catch (Exception $e) {
        $logger->error('Błąd podczas przetwarzania wydarzeń: ' . $e->getMessage());
        return json_encode(['success' => false, 'message' => 'Błąd w trakcie przetwarzania wydarzeń: ' . $e->getMessage()]);
    }
}

function getMetarData(Logger $logger): false|string {
    try {
        $logger->info('Rozpoczęto pobieranie danych METAR.');

        $metarService = new \src\utilities\MetarService($logger);
        $metarData = $metarService->getMetar('EPPO');
        $logger->debug('Utworzono instancję metarService.');

        if (!empty($metarData)) {
            $response = [];
            $response[] = [
                    'metar' => htmlspecialchars($event['summary'] ?? ''),
            ];
            $logger->debug('Pomyślnie pobrano dane depeszy METAR.');
            return json_encode(['success' => true, 'data' => $response]);
        } else {
            $logger->warning('Brak dostępnych danych o wydarzeniach.');
            return json_encode(['success' => false, 'message' => 'Brak danych o wydarzeniach.']);
        }
    } catch (Exception $e) {
        $logger->error('Błąd podczas przetwarzania wydarzeń: ' . $e->getMessage());
        return json_encode(['success' => false, 'message' => 'Błąd w trakcie przetwarzania wydarzeń: ' . $e->getMessage()]);
    }
}