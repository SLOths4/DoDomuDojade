<?php

namespace src\controllers;

use DateTimeZone;
use Exception;
use Psr\Log\LoggerInterface;
use src\core\Controller;
use src\infrastructure\helpers\SessionHelper;
use src\service\AnnouncementService;
use src\service\CountdownService;
use src\service\ModuleService;
use src\service\TramService;
use src\service\UserService;
use src\service\WeatherService;

class DisplayController extends Controller
{
    public function __construct(
        private readonly LoggerInterface     $logger,
        private readonly WeatherService      $weatherService,
        private readonly ModuleService       $moduleService,
        private readonly TramService         $tramService,
        private readonly AnnouncementService $announcementsService,
        private readonly UserService         $userService,
        private readonly CountdownService    $countdownService,
        private readonly array               $StopIDs,
    )
    {
        SessionHelper::start();
    }

    /**
     * @throws Exception
     */
    private function isModuleVisible(string $module): bool
    {
        $isModuleVisible = $this->moduleService->isVisible($module);
        if ($isModuleVisible) {
            $this->logger->info("$module is active.");
            return true;
        }
        $this->logger->info("$module is active");
        return false;
    }

    public function index(): void
    {
        $this->render('display');
    }

    public function getDepartures (): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');

            try {
                $this->logger->debug('Rozpoczęto pobieranie danych tramwajowych.');

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
                        $stopDepartures = $this->tramService->getTimes($stopId);
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
            header('Content-Type: application/json');

            try {
                $this->logger->debug('Rozpoczęto pobieranie ogłoszeń.');

                if (!$this->isModuleVisible('announcements')) {
                    $this->logger->debug("Announcements module is not active.");
                    echo json_encode(
                        [
                            'success' => true,
                            'is_active' => false,
                            'data' => null
                        ]
                    );
                    exit;
                }

                $announcements = $this->announcementsService->getValid();
                $this->logger->debug('Pobrano listę ogłoszeń z bazy danych.');

                $response = [];

                foreach ($announcements as $announcement) {
                    $user = $this->userService->getById($announcement->userId);
                    $author = $user->username ?? 'Nieznany użytkownik';

                    $response[] = [
                        'title' => htmlspecialchars($announcement->title),
                        'author' => $author,
                        'date' => htmlspecialchars($announcement->date->format('Y-m-d')),
                        'validUntil' => htmlspecialchars($announcement->validUntil->format('Y-m-d')),
                        'text' => htmlspecialchars($announcement->text),
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

                $currentCountdown = $this->countdownService->getCurrent();

                if (!empty($currentCountdown)) {
                    $dt = $currentCountdown->countTo->setTimezone(new DateTimeZone('Europe/Warsaw'));

                    $response[] = [
                        'title' => htmlspecialchars($currentCountdown->title),
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

                $weatherServiceResponse = $this->weatherService->getWeather();

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
}