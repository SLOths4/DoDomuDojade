<?php

namespace src\controllers;

use DateTime;
use Exception;
use src\core\Controller;
use src\models\AnnouncementsModel;
use src\models\CalendarModel;
use src\models\CountdownModel;
use src\models\ModuleModel;
use src\models\TramModel;
use src\models\UserModel;
use src\models\WeatherModel;

class DisplayController extends Controller
{
    private ModuleModel $moduleModel;
    private TramModel $tramModel;
    private AnnouncementsModel $announcementsModel;
    private UserModel $userModel;
    private CountdownModel $countdownModel;
    private CalendarModel $calendarModel;
    private WeatherModel $weatherModel;

    /**
     * @throws Exception
     */
    function __construct()
    {
        ModuleModel::initDatabase();

        $ztmURL = self::getEnvVariable('ZTM_URL');
        $icalURL = self::getEnvVariable('CALENDAR_URL');

        $this->moduleModel = new ModuleModel();
        $this->tramModel = new TramModel($ztmURL);
        $this->announcementsModel = new AnnouncementsModel();
        $this->userModel = new UserModel();
        $this->countdownModel = new CountdownModel();
        $this->calendarModel = new CalendarModel($icalURL);
        $this->weatherModel = new WeatherModel();
    }

    public function index(): void
    {
        $this->render('display');
    }

    public function getVersion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            try {
                echo json_encode(['success' => true, 'is_active' => true, 'data' => trim(shell_exec('git describe --tags --abbrev=0'))]);
                exit;
            } catch (Exception $e) {
                self::$logger->error('Unable to fetch version', ['error' => $e->getMessage()]);
                exit;
            }
        }
    }

    public function getDepartures (): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::$logger->debug('Rozpoczęto pobieranie danych tramwajowych.');
            header('Content-Type: application/json');
            try {
                if (!$this->isModuleVisible('tram')) {
                    self::$logger->debug("Moduł tram nie jest aktywny.");
                    echo json_encode(
                        [
                            'success' => true,
                            'is_active' => false,
                            'data' => null
                        ]
                    );
                    exit;
                }

                $stopsIdS = self::getConfigVariable('STOPS_IDS');
                $departures = [];


                foreach ($stopsIdS as $stopId) {
                    $stopDepartures = $this->tramModel->getTimes($stopId);

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
                        self::$logger->warning("Brak dostępnych danych o odjazdach dla przystanku: $stopId.");
                    }
                }

                usort($departures, function ($a, $b) {
                    return $a['minutes'] <=> $b['minutes'];
                });

                if (!empty($departures)) {
                    self::$logger->debug('Pomyślnie pobrano dane tramwajowe.');
                    echo json_encode(['success' => true, 'is_active' => true, 'data' => $departures]);
                } else {
                    self::$logger->warning('Brak danych o odjazdach dla wszystkich przystanków.');
                    echo json_encode(['success' => false, 'is_active' => true, 'message' => 'Brak danych o odjazdach dla wybranych przystanków.']);
                }

                exit;
            } catch (Exception $e) {
                self::$logger->error('Błąd podczas przetwarzania danych tramwajowych: ' . $e->getMessage());
                echo json_encode(['success' => false, 'is_active' => true, 'message' => 'Błąd w trakcie przetwarzania danych tramwajowych: ' . $e->getMessage()]);
                exit;
            }
        }
    }

    public function getAnnouncements() : void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::$logger->debug('Rozpoczęto pobieranie ogłoszeń.');
            header('Content-Type: application/json');
            try {
                if (!$this->isModuleVisible('announcement')) {
                    self::$logger->debug("Moduł announcement nie jest aktywny.");
                    echo json_encode(
                        [
                            'success' => true,
                            'is_active' => false,
                            'data' => null
                        ]
                    );
                    exit;
                }


                $announcements = $this->announcementsModel->getValidAnnouncements();
                self::$logger->debug('Pobrano listę ogłoszeń z bazy danych.');

                $response = [];

                foreach ($announcements as $announcement) {
                    $user = $this->userModel->getUserById($announcement['user_id']);
                    $author = $user['username'] ?? 'Nieznany użytkownik';

                    $response[] = [
                        'title' => htmlspecialchars($announcement['title']),
                        'author' => $author,
                        'date' => htmlspecialchars($announcement['date']),
                        'validUntil' => htmlspecialchars($announcement['valid_until']),
                        'text' => htmlspecialchars($announcement['text']),
                    ];
                }

                self::$logger->debug('Pomyślnie przetworzono dane ogłoszeń.');
                echo json_encode([
                    'success' => true,
                    'data' => $response
                ]);
                exit;
            } catch (Exception $e) {
                self::$logger->error('Błąd podczas pobierania ogłoszeń: ' . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Error fetching announcements' . $e->getMessage()
                ]);
                exit;
            }
        }
    }

    public function getCountdown() : void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            try {
                self::$logger->debug('Rozpoczęto pobieranie danych odliczania.');

                if (!$this->isModuleVisible('countdown')) {
                    self::$logger->debug("Moduł odliczania nie jest aktywny.");
                    echo json_encode(
                        [
                            'success' => true,
                            'is_active' => false,
                            'data' => null
                        ]
                    );
                    exit;
                }

                $currentCountdown = $this->countdownModel->getCurrentCountdown();

                if (!empty($currentCountdown)) {
                    $response[] = [
                        'title' => htmlspecialchars($currentCountdown['title']),
                        'count_to' => new DateTime($currentCountdown['count_to'])->format(DateTime::ATOM)
                    ];
                    self::$logger->debug('Pomyślnie pobrano dane obecnego odliczania.');
                    echo json_encode(['success' => true, 'data' => $response]);
                } else {
                    self::$logger->warning('Brak dostępnych danych odliczania.');
                    echo json_encode(['success' => false, 'message' => 'Brak dostępnych danych odliczania.']);
                }
                exit;
            } catch (Exception $e) {
                self::$logger->error('Błąd podczas przetwarzania danych odliczania: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Błąd podczas przetwarzania danych odliczania: ' . $e->getMessage()]);
                exit;
            }
        }
    }

    public function getEvents() : void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            try {
                self::$logger->debug('Rozpoczęto pobieranie wydarzeń.');

                if (!$this->isModuleVisible('calendar')) {
                    self::$logger->debug("Moduł calendar nie jest aktywny.");
                    echo json_encode(
                        [
                            'success' => true,
                            'is_active' => false,
                            'data' => null
                        ]
                    );
                    exit;
                }

                $calendarServiceResponse = $this->calendarModel->get_events();

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
                    self::$logger->debug('Pomyślnie pobrano dane wydarzenia.');
                    echo json_encode(['success' => true, 'data' => $response]);
                } else {
                    self::$logger->warning('Brak dostępnych danych o wydarzeniach.');
                    echo json_encode(['success' => false, 'message' => 'Brak danych o wydarzeniach.']);
                }
                exit;
            } catch (Exception $e) {
                self::$logger->error('Błąd podczas przetwarzania wydarzeń: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Błąd w trakcie przetwarzania wydarzeń: ' . $e->getMessage()]);
                exit;
            }
        }
    }

    public function getWeather() : void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            try {
                self::$logger->debug('Rozpoczęto przetwarzanie danych pogodowych.');

                if (!$this->isModuleVisible('weather')) {
                    self::$logger->debug("Moduł weather nie jest aktywny.");
                    echo json_encode(
                        [
                            'success' => true,
                            'is_active' => false,
                            'data' => null
                        ]
                    );
                    exit;
                }

                $weatherServiceResponse = $this->weatherModel->getWeather();

                if (empty($weatherServiceResponse)) {
                    self::$logger->warning('Brak danych pogodowych.');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Brak danych pogodowych.'
                    ]);
                    exit;
                }
                self::$logger->debug('Pomyślnie pobrano dane pogodowe.');

                echo json_encode([
                    'success' => true,
                    'data' => [
                        'temperature' => htmlspecialchars($weatherServiceResponse['imgw_temperature'] ?? 'Brak danych'),
                        'pressure' => htmlspecialchars($weatherServiceResponse['imgw_pressure'] ?? 'Brak danych'),
                        'airlyIndex' => $weatherServiceResponse['airly_index_value'] !== null
                            ? htmlspecialchars($weatherServiceResponse['airly_index_value'])
                            : 'Brak danych'
                    ]
                ]);
                exit;
            } catch (Exception $e) {
                self::$logger->error('Błąd podczas pobierania danych pogodowych: ' . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Błąd pobierania danych pogodowych: ' . $e->getMessage()
                ]);
                exit;
            }
        }
    }

    private function isModuleVisible(string $module): bool
    {
        $isModuleVisible = $this->moduleModel->isModuleVisible($module);
        if ($isModuleVisible) {
            self::$logger->info("Moduł $module jest aktywny.");
            return true;
        }
        self::$logger->info("Moduł $module jest nieaktywny.");
        return false;
    }
}