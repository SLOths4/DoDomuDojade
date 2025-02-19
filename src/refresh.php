<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use src\utilities\CountdownService;
use src\utilities\UserService;
use src\utilities\TramService;
use src\utilities\WeatherService;
use src\utilities\AnnouncementService;
use src\utilities\CalendarService;
use src\utilities\MetarService;
use src\utilities\ModuleService;
use Monolog\Logger;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__.'/../');
$dotenv->load();

$db_host = $_ENV['DB_HOST'] ?? null;
$db_username = $_ENV['DB_USERNAME'] ?? null;
$db_password = $_ENV['DB_PASSWORD'] ?? null;
$ztm_url = $_ENV['ZTM_URL'];
$ical_url = $_ENV['CALENDAR_URL'];
$metar_url = $_ENV['AIRPORT_URL'] . $_ENV['AIRPORT_CODE'];

global $logger;
$logger = new Monolog\Logger('AppHandler');
$logger->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . '/log/app.log', Monolog\Level::Debug));

global $pdo;
if ($db_host && str_starts_with($db_host, 'sqlite:')) {
    $pdo = new PDO($db_host);
} elseif (!empty($db_password) && !empty($db_username)) {
    $pdo = new PDO($db_host, $db_username, $db_password);
} else {
    throw new RuntimeException('Nieprawidłowa konfiguracja bazy danych.');
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$function = $_GET['function'] ?? 'default';

function isModuleActive(string $module): bool
{
    global $pdo;
    global $logger;
    $moduleService = new ModuleService($pdo, $logger);
    if ($moduleService->isModuleVisible($module)) {
        $logger->info("Moduł $module jest aktywny.");
        return true;
    }
    $logger->info("Moduł $module jest nieaktywny.");
    return false;
}

function getVersion(): string
{
    return trim(shell_exec('git describe --tags --abbrev=0'));
}

switch ($function) {
    case 'tramData':
        echo json_encode(getTramData($logger, $ztm_url));
        break;
    case 'announcementsData':
        echo json_encode(getAnnouncementsData($logger, $pdo));
        break;
    case 'weatherData':
        echo json_encode(getWeatherData($logger));
        break;
    case 'calendarData':
        echo json_encode(getCalendarData($logger, $ical_url));
        break;
    case 'countdownData':
        echo json_encode(getCountdownData($logger, $pdo));
        break;
    case 'metarData':
        echo json_encode(getMetarData($logger));
        break;
    case 'getVersion':
        echo json_encode(['version' => getVersion()]);
        break;
    default:
        echo json_encode(['error' => 'Nieznana funkcja']);
        break;
}

function getTramData(Logger $logger, string $ztmURL): false|string
{
    try {
        $logger->info('Rozpoczęto pobieranie danych tramwajowych.');

        if (!isModuleActive('tram')) {
            $logger->debug("Moduł tram nie jest aktywny.");
            return json_encode(
                [
                    'success' => true,
                    'is_active' => false,
                    'data' => null
                ]
            );
        }

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
            return json_encode(['success' => true, 'is_active' => true, 'data' => $departures]);
        } else {
            $logger->warning('Brak danych o odjazdach dla wszystkich przystanków.');
            return json_encode(['success' => false, 'is_active' => true, 'message' => 'Brak danych o odjazdach dla wybranych przystanków.']);
        }
    } catch (Exception $e) {
        $logger->error('Błąd podczas przetwarzania danych tramwajowych: ' . $e->getMessage());
        return json_encode(['success' => false, 'is_active' => true, 'message' => 'Błąd w trakcie przetwarzania danych tramwajowych: ' . $e->getMessage()]);
    }
}

function getAnnouncementsData(Logger $logger, PDO $pdo): false|string
{
    try {

        if (!isModuleActive('announcement')) {
            $logger->debug("Moduł announcement nie jest aktywny.");
            return json_encode(
                [
                    'success' => true,
                    'is_active' => false,
                    'data' => null
                ]
            );
        }

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

        if (!isModuleActive('weather')) {
            $logger->debug("Moduł weather nie jest aktywny.");
            return json_encode(
                [
                    'success' => true,
                    'is_active' => false,
                    'data' => null
                ]
            );
        }

        $weatherService = new WeatherService($logger);
        $logger->debug('Utworzono instancję WeatherService.');
        $weatherServiceResponse = $weatherService->getWeather();
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

        if (!isModuleActive('calendar')) {
            $logger->debug("Moduł calendar nie jest aktywny.");
            return json_encode(
                [
                    'success' => true,
                    'is_active' => false,
                    'data' => null
                ]
            );
        }

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
        $metarService = new MetarService($logger);
        $metarData = $metarService->getMetar('EPPO');
        $logger->debug('Utworzono instancję metarService.');

        if (!empty($metarData)) {
            $response[] = [
                    'metar' => htmlspecialchars(htmlspecialchars($metarData) ?? ''),
            ];
            $logger->debug('Pomyślnie pobrano dane depeszy METAR.');
            return json_encode(['success' => true, 'data' => $response]);
        } else {
            $logger->warning('Brak dostępnych depeszy METAR.');
            return json_encode(['success' => false, 'message' => 'Brak dostępnych depeszy METAR.']);
        }
    } catch (Exception $e) {
        $logger->error('Błąd podczas przetwarzania depeszy METAR: ' . $e->getMessage());
        return json_encode(['success' => false, 'message' => 'Błąd w trakcie przetwarzania przetwarzania depeszy METAR: ' . $e->getMessage()]);
    }
}

function getCountdownData(Logger $logger, PDO $PDO): false|string
{
    try {
        $logger->info('Rozpoczęto pobieranie danych odliczania.');

        if (!isModuleActive('countdown')) {
            $logger->debug("Moduł odliczania nie jest aktywny.");
            return json_encode(
                [
                    'success' => true,
                    'is_active' => false,
                    'data' => null
                ]
            );
        }

        $countdownService = new CountdownService($logger, $PDO);
        $logger->debug('Utworzono instancję countdownService.');
        $currentCountdown = $countdownService->getCurrentCountdown();

        if (!empty($currentCountdown)) {
            $response[] = [
                'title' => htmlspecialchars($currentCountdown['title']),
                'count_to' => htmlspecialchars($currentCountdown['count_to'])
            ];
            $logger->debug('Pomyślnie pobrano dane obecnego odliczania.');
            return json_encode(['success' => true, 'data' => $response]);
        } else {
            $logger->warning('Brak dostępnych danych odliczania.');
            return json_encode(['success' => false, 'message' => 'Brak dostępnych danych odliczania.']);
        }
    } catch (Exception $e) {
        $logger->error('Błąd podczas przetwarzania danych odliczania: ' . $e->getMessage());
        return json_encode(['success' => false, 'message' => 'Błąd podczas przetwarzania danych odliczania: ' . $e->getMessage()]);
    }
}