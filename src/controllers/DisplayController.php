<?php

namespace src\controllers;

use DateTimeZone;
use Exception;
use Psr\Log\LoggerInterface;
use src\core\Controller;
use src\infrastructure\traits\SendResponseTrait;
use src\security\AuthenticationService;
use src\security\CsrfService;
use src\service\AnnouncementService;
use src\service\CountdownService;
use src\service\ModuleService;
use src\service\TramService;
use src\service\UserService;
use src\service\WeatherService;

class DisplayController extends Controller
{
    use SendResponseTrait;
    public function __construct(
        AuthenticationService $authenticationService,
        CsrfService $csrfService,
        LoggerInterface $logger,
        private readonly WeatherService      $weatherService,
        private readonly ModuleService       $moduleService,
        private readonly TramService         $tramService,
        private readonly AnnouncementService $announcementsService,
        private readonly UserService         $userService,
        private readonly CountdownService    $countdownService,
        private readonly array               $StopIDs,
    )
    {
        parent::__construct($authenticationService, $csrfService, $logger);
    }

    /**
     * Renders display page
     */
    public function index(): void
    {
        $this->render('display');
    }

    /**
     * @throws Exception
     */
    private function isModuleVisible(string $module): bool
    {
        $isModuleVisible = $this->moduleService->isVisible($module);
        if ($isModuleVisible) {
            $this->logger->debug("$module is active.");
            return true;
        }
        $this->logger->debug("$module is not active");
        return false;
    }

    public function getDepartures(): void
    {
        try {
            $this->validateMethod($_SERVER['REQUEST_METHOD'], 'GET');

            if (!$this->isModuleVisible('tram')) {
                $this->sendSuccess(
                    [
                        'is_active' => false,
                        'departures' => null
                    ]
                );
            }

            $departures = [];
            foreach ($this->StopIDs as $stopId) {
                try {
                    $stopDepartures = $this->tramService->getTimes($stopId);
                } catch (Exception) {
                    $this->logger->warning("No departures found for stop $stopId");
                    continue;
                }

                if (isset($stopDepartures['times']) && is_array($stopDepartures['times'])) {
                    foreach ($stopDepartures['times'] as $departure) {
                        $departures[] = [
                            'stopId' => $stopId,
                            'line' => (string)$departure['line'],
                            'minutes' => (int)$departure['minutes'],
                            'direction' => (string)$departure['direction'],
                        ];
                    }
                } else {
                    $this->logger->warning("Brak dostępnych danych o odjazdach dla przystanku: $stopId.");
                }
            }

            usort($departures, static fn($a, $b) => $a['minutes'] <=> $b['minutes']);

            if (!empty($departures)) {
                $this->sendSuccess([
                    'is_active' => true,
                    'departures' => $departures
                ]);
            } else {
                $this->sendSuccess([
                    'success' => false,
                    'is_active' => true
                ], 'No departures found for provided stop IDs');
            }
        } catch (Exception) {
            $this->sendError('Error processing tram data', 500, []);
        }
    }

    public function getAnnouncements(): void
    {
        try {
            $this->validateMethod($_SERVER['REQUEST_METHOD'], 'GET');

            if (!$this->isModuleVisible('announcements')) {
                $this->sendSuccess(
                    [
                        'is_active' => false,
                        'announcements' => null
                    ]
                );
            }

            $announcements = $this->announcementsService->getValid();

            $response = [];
            foreach ($announcements as $announcement) {
                $user = $this->userService->getById($announcement->userId);
                $author = $user->username ?? 'Nieznany użytkownik';

                $response[] = [
                    'title' => $announcement->title,
                    'author' => $author,
                    'date' => $announcement->date->format('Y-m-d'),
                    'validUntil' => $announcement->validUntil->format('Y-m-d'),
                    'text' => $announcement->text,
                ];
            }

            $this->sendSuccess(
                [
                    'is_active' => true,
                    'announcements' => $response
                ]
            );
        } catch (Exception) {
            $this->sendError(
                'Error fetching announcements',
                500,
                []
            );
        }
    }

    public function getCountdown(): void
    {
        try {
            $this->validateMethod($_SERVER['REQUEST_METHOD'], 'GET');

            if (!$this->isModuleVisible('countdown')) {
                $this->sendSuccess(
                    [
                        'is_active' => false,
                        'title' => null,
                        'count_to' => null
                    ]
                );
            }

            $currentCountdown = $this->countdownService->getCurrent();

            if ($currentCountdown) {
                $dt = $currentCountdown->countTo->setTimezone(new DateTimeZone('Europe/Warsaw'));
                $this->sendSuccess([
                    'is_active' => true,
                    'title' => $currentCountdown->title,
                    'count_to' => $dt->getTimestamp()
                ]);
            } else {
                $this->sendSuccess([
                    'is_active' => true,
                    'title' => null,
                    'count_to' => null
                ], 'No countdown available');
            }
        } catch (Exception) {
            $this->sendError('Błąd podczas przetwarzania danych odliczania.', 500, []);
        }
    }

    public function getWeather(): void
    {
        try {
            $this->validateMethod($_SERVER['REQUEST_METHOD'], 'GET');

            if (!$this->isModuleVisible('weather')) {
                $this->sendSuccess([
                    'is_active' => false,
                    'weather' => null
                ]);
            }

            $weatherServiceResponse = $this->weatherService->getWeather();

            if (empty($weatherServiceResponse)) {
                $this->sendSuccess([
                    'success' => true,
                    'weather' => null
                ], 'Brak danych pogodowych.');
            }

            $this->sendSuccess([
                'is_active' => true,
                'weather' => [
                    'temperature' => (string)($weatherServiceResponse['imgw_temperature'] ?? 'Brak danych'),
                    'pressure' => (string)($weatherServiceResponse['imgw_pressure'] ?? 'Brak danych'),
                    'airlyAdvice' => $weatherServiceResponse['airly_index_advice'] !== null
                        ? (string)$weatherServiceResponse['airly_index_advice']
                        : 'Brak danych',
                    'airlyDescription' => $weatherServiceResponse['airly_index_description'] !== null
                        ? (string)$weatherServiceResponse['airly_index_description']
                        : 'Brak danych',
                    'airlyColour' => $weatherServiceResponse['airly_index_colour'] !== null
                        ? (string)$weatherServiceResponse['airly_index_colour']
                        : 'Brak danych'
                ]
            ]);
        } catch (Exception) {
            $this->sendError(
                'Błąd pobierania danych pogodowych.',
                500,
                    []
            );
        }
    }
}