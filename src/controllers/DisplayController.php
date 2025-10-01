<?php

namespace src\controllers;

use DateTime;
use DateTimeZone;
use Exception;
use src\core\Controller;
use src\core\SessionHelper;
use src\models\AnnouncementsModel;
use src\models\CalendarModel;
use src\models\CountdownModel;
use src\models\ModuleModel;
use src\models\TramModel;
use src\models\UserModel;
use src\models\WeatherModel;
use Psr\Log\LoggerInterface;

class DisplayController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly WeatherModel $weatherModel,
        private readonly ModuleModel $moduleModel,
        private readonly TramModel $tramModel,
        private readonly AnnouncementsModel $announcementsModel,
        private readonly UserModel $userModel,
        private readonly CountdownModel $countdownModel,
        private readonly CalendarModel $calendarModel,
        private readonly array $StopIDs,
    )
    {
        SessionHelper::start();
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
                $this->logger->error('Unable to fetch version', ['error' => $e->getMessage()]);
                exit;
            }
        }
    }

    public function getDepartures (): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->logger->debug('Rozpoczęto pobieranie danych tramwajowych.');
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');

            try {
                if (!$this->isModuleVisible('tram')) {
                    $this->logger->debug("Moduł tram nie jest aktywny.");
                    echo json_encode(
                        [
                            'success' => true,
                            'is_active' => false,
                            'data' => null
                        ]
                    );
                    exit;
                }

                $stopsIdS = $this->StopIDs;
                $departures = [];


                foreach ($stopsIdS as $stopId) {
                    try {
                        $stopDepartures = $this->tramModel->getTimes($stopId);
                    } catch (Exception $e) {
                        continue;
                    }

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
                        $this->logger->warning("Brak dostępnych danych o odjazdach dla przystanku: $stopId.");
                    }
                }

                usort($departures, function ($a, $b) {
                    return $a['minutes'] <=> $b['minutes'];
                });

                if (!empty($departures)) {
                    $this->logger->debug('Pomyślnie pobrano dane tramwajowe.');
                    echo json_encode(['success' => true, 'is_active' => true, 'data' => $departures]);
                } else {
                    $this->logger->warning('Brak danych o odjazdach dla wszystkich przystanków.');
                    echo json_encode(['success' => false, 'is_active' => true, 'message' => 'Brak danych o odjazdach dla wybranych przystanków.']);
                }

                exit;
            } catch (Exception $e) {
                $this->logger->error('Błąd podczas przetwarzania danych tramwajowych: ' . $e->getMessage());
                echo json_encode(['success' => false, 'is_active' => true, 'message' => 'Błąd w trakcie przetwarzania danych tramwajowych: ' . $e->getMessage()]);
                exit;
            }
        }
    }

    public function getAnnouncements() : void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->logger->debug('Rozpoczęto pobieranie ogłoszeń.');
            header('Content-Type: application/json');
            try {
                if (!$this->isModuleVisible('announcement')) {
                    $this->logger->debug("Moduł announcement nie jest aktywny.");
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
                $this->logger->debug('Pobrano listę ogłoszeń z bazy danych.');

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

                $this->logger->debug('Pomyślnie przetworzono dane ogłoszeń.');
                echo json_encode([
                    'success' => true,
                    'data' => $response
                ]);
                exit;
            } catch (Exception $e) {
                $this->logger->error('Błąd podczas pobierania ogłoszeń: ' . $e->getMessage());
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
                $this->logger->debug('Rozpoczęto pobieranie danych odliczania.');

                if (!$this->isModuleVisible('countdown')) {
                    $this->logger->debug("Moduł odliczania nie jest aktywny.");
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
                    $dt = new DateTime($currentCountdown['count_to'], new DateTimeZone('Europe/Warsaw') );

                    $response[] = [
                        'title' => htmlspecialchars($currentCountdown['title']),
                        'count_to' => $dt->getTimestamp()
                    ];
                    $this->logger->debug('Pomyślnie pobrano dane obecnego odliczania.');
                    echo json_encode(['success' => true, 'data' => $response]);
                } else {
                    $this->logger->warning('Brak dostępnych danych odliczania.');
                    echo json_encode(['success' => false, 'message' => 'Brak dostępnych danych odliczania.']);
                }
                exit;
            } catch (Exception $e) {
                $this->logger->error('Błąd podczas przetwarzania danych odliczania: ' . $e->getMessage());
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
                $this->logger->debug('Rozpoczęto pobieranie wydarzeń.');

                if (!$this->isModuleVisible('calendar')) {
                    $this->logger->debug("Moduł calendar nie jest aktywny.");
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
                    $this->logger->debug('Pomyślnie pobrano dane wydarzenia.');
                    echo json_encode(['success' => true, 'data' => $response]);
                } else {
                    $this->logger->warning('Brak dostępnych danych o wydarzeniach.');
                    echo json_encode(['success' => false, 'message' => 'Brak danych o wydarzeniach.']);
                }
                exit;
            } catch (Exception $e) {
                $this->logger->error('Błąd podczas przetwarzania wydarzeń: ' . $e->getMessage());
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
                $this->logger->debug('Rozpoczęto przetwarzanie danych pogodowych.');

                if (!$this->isModuleVisible('weather')) {
                    $this->logger->debug("Moduł weather nie jest aktywny.");
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
                    $this->logger->warning('Brak danych pogodowych.');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Brak danych pogodowych.'
                    ]);
                    exit;
                }
                $this->logger->debug('Pomyślnie pobrano dane pogodowe.');

                echo json_encode([
                    'success' => true,
                    'data' => [
                        'temperature' => htmlspecialchars($weatherServiceResponse['imgw_temperature'] ?? 'Brak danych'),
                        'pressure' => htmlspecialchars($weatherServiceResponse['imgw_pressure'] ?? 'Brak danych'),
                        'airlyAdvice' => $weatherServiceResponse['airly_index_advice'] !== null
                            ? htmlspecialchars($weatherServiceResponse['airly_index_advice'])
                            : 'Brak danych',
                        'airlyDescription' => $weatherServiceResponse['airly_index_description'] !== null
                            ? htmlspecialchars($weatherServiceResponse['airly_index_description'])
                            : 'Brak danych',
                        'airlyColour' => $weatherServiceResponse['airly_index_colour'] !== null
                            ? htmlspecialchars($weatherServiceResponse['airly_index_colour'])
                            : 'Brak danych'
                    ]
                ]);
                exit;
            } catch (Exception $e) {
                $this->logger->error('Błąd podczas pobierania danych pogodowych: ' . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Błąd pobierania danych pogodowych: ' . $e->getMessage()
                ]);
                exit;
            }
        }
    }

    /**
     * @throws Exception
     */
    private function isModuleVisible(string $module): bool
    {
        $isModuleVisible = $this->moduleModel->isModuleVisible($module);
        if ($isModuleVisible) {
            $this->logger->info("Moduł $module jest aktywny.");
            return true;
        }
        $this->logger->info("Moduł $module jest nieaktywny.");
        return false;
    }
}