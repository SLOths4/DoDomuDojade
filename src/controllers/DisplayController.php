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
        $this->logger->info("$module is not active");
        return false;
    }

    private function jsonHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
    }

    private function jsonResponse(array $payload): void
    {
        try {
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            $this->logger->error("Error serializing JSON response: " . $e->getMessage());
            echo '{"success":false,"message":"Serialization error"}';
        }
    }

    private function ensurePost(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }
        $this->jsonHeaders();
        return true;
    }

    public function index(): void
    {
        $this->render('display');
    }

    public function getDepartures(): void
    {
        if (!$this->ensurePost()) { return; }

        try {
            $this->logger->debug('Rozpoczęto pobieranie danych tramwajowych.');

            if (!$this->isModuleVisible('tram')) {
                $this->logger->debug("Moduł tram nie jest aktywny.");
                $this->jsonResponse(['success' => true, 'is_active' => false, 'data' => null]);
                return;
            }

            $departures = [];
            foreach ($this->StopIDs as $stopId) {
                try {
                    $stopDepartures = $this->tramService->getTimes($stopId);
                } catch (Exception) {
                    continue;
                }

                if (isset($stopDepartures['success']['times']) && is_array($stopDepartures['success']['times'])) {
                    foreach ($stopDepartures['success']['times'] as $departure) {
                        $departures[] = [
                            'stopId' => $stopId,
                            'line' => htmlspecialchars((string)$departure['line']),
                            'minutes' => (int)htmlspecialchars((string)$departure['minutes']),
                            'direction' => htmlspecialchars((string)$departure['direction']),
                        ];
                    }
                } else {
                    $this->logger->warning("Brak dostępnych danych o odjazdach dla przystanku: $stopId.");
                }
            }

            usort($departures, static fn($a, $b) => $a['minutes'] <=> $b['minutes']);

            if (!empty($departures)) {
                $this->logger->debug('Pomyślnie pobrano dane tramwajowe.');
                $this->jsonResponse(['success' => true, 'is_active' => true, 'data' => $departures]);
            } else {
                $this->logger->warning('Brak danych o odjazdach dla wszystkich przystanków.');
                $this->jsonResponse(['success' => false, 'is_active' => true, 'message' => 'Brak danych o odjazdach dla wybranych przystanków.']);
            }
        } catch (Exception $e) {
            $this->logger->error('Błąd podczas przetwarzania danych tramwajowych: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'is_active' => true, 'message' => 'Błąd w trakcie przetwarzania danych tramwajowych.']);
        }
    }

    public function getAnnouncements(): void
    {
        if (!$this->ensurePost()) { return; }

        try {
            $this->logger->debug('Rozpoczęto pobieranie ogłoszeń.');

            if (!$this->isModuleVisible('announcements')) {
                $this->logger->debug("Announcements module is not active.");
                $this->jsonResponse(['success' => true, 'is_active' => false, 'data' => null]);
                return;
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
            $this->jsonResponse(['success' => true, 'is_active' => true, 'data' => $response]);
        } catch (Exception $e) {
            $this->logger->error('Błąd podczas pobierania ogłoszeń: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error fetching announcements']);
        }
    }

    public function getCountdown(): void
    {
        if (!$this->ensurePost()) { return; }

        try {
            $this->logger->debug('Rozpoczęto pobieranie danych odliczania.');

            if (!$this->isModuleVisible('countdown')) {
                $this->logger->debug("Moduł odliczania nie jest aktywny.");
                $this->jsonResponse(['success' => true, 'is_active' => false, 'data' => null]);
                return;
            }

            $currentCountdown = $this->countdownService->getCurrent();

            if ($currentCountdown) {
                $dt = $currentCountdown->countTo->setTimezone(new DateTimeZone('Europe/Warsaw'));
                $response = [[
                    'title' => htmlspecialchars($currentCountdown->title),
                    'count_to' => $dt->getTimestamp()
                ]];
                $this->logger->debug('Pomyślnie pobrano dane obecnego odliczania.');
                $this->jsonResponse(['success' => true, 'data' => $response]);
            } else {
                $this->logger->warning('Brak dostępnych danych odliczania.');
                $this->jsonResponse(['success' => false, 'message' => 'Brak dostępnych danych odliczania.']);
            }
        } catch (Exception $e) {
            $this->logger->error('Błąd podczas przetwarzania danych odliczania: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Błąd podczas przetwarzania danych odliczania.']);
        }
    }

    public function getWeather(): void
    {
        if (!$this->ensurePost()) { return; }

        try {
            $this->logger->debug('Rozpoczęto przetwarzanie danych pogodowych.');

            if (!$this->isModuleVisible('weather')) {
                $this->logger->debug("Moduł weather nie jest aktywny.");
                $this->jsonResponse(['success' => true, 'is_active' => false, 'data' => null]);
                return;
            }

            $weatherServiceResponse = $this->weatherService->getWeather();

            if (empty($weatherServiceResponse)) {
                $this->logger->warning('Brak danych pogodowych.');
                $this->jsonResponse(['success' => false, 'message' => 'Brak danych pogodowych.']);
                return;
            }

            $this->logger->debug('Pomyślnie pobrano dane pogodowe.');
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'temperature' => htmlspecialchars((string)($weatherServiceResponse['imgw_temperature'] ?? 'Brak danych')),
                    'pressure' => htmlspecialchars((string)($weatherServiceResponse['imgw_pressure'] ?? 'Brak danych')),
                    'airlyAdvice' => $weatherServiceResponse['airly_index_advice'] !== null
                        ? htmlspecialchars((string)$weatherServiceResponse['airly_index_advice'])
                        : 'Brak danych',
                    'airlyDescription' => $weatherServiceResponse['airly_index_description'] !== null
                        ? htmlspecialchars((string)$weatherServiceResponse['airly_index_description'])
                        : 'Brak danych',
                    'airlyColour' => $weatherServiceResponse['airly_index_colour'] !== null
                        ? htmlspecialchars((string)$weatherServiceResponse['airly_index_colour'])
                        : 'Brak danych'
                ]
            ]);
        } catch (Exception $e) {
            $this->logger->error('Błąd podczas pobierania danych pogodowych: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Błąd pobierania danych pogodowych.']);
        }
    }
}