<?php

namespace App\Http\Controller;

use App\Application\UseCase\AnnouncementService;
use App\Application\UseCase\CountdownService;
use App\Application\UseCase\ModuleService;
use App\Application\UseCase\Quote\FetchActiveQuoteUseCase;
use App\Application\UseCase\Word\FetchActiveWordUseCase;
use App\Application\UseCase\UserService;
use App\infrastructure\Security\AuthenticationService;
use App\infrastructure\Security\CsrfService;
use App\Infrastructure\Service\CalendarService;
use App\Infrastructure\Service\TramService;
use App\Infrastructure\Service\WeatherService;
use App\infrastructure\Trait\SendResponseTrait;
use DateTime;
use DateTimeZone;
use Exception;
use Google\Service\Calendar\EventDateTime;
use Psr\Log\LoggerInterface;

class DisplayController extends BaseController
{
    use SendResponseTrait;
    public function __construct(
        AuthenticationService                    $authenticationService,
        CsrfService                              $csrfService,
        LoggerInterface                          $logger,
        private readonly WeatherService          $weatherService,
        private readonly ModuleService           $moduleService,
        private readonly TramService             $tramService,
        private readonly AnnouncementService     $announcementsService,
        private readonly UserService             $userService,
        private readonly CountdownService        $countdownService,
        private readonly CalendarService         $calendarService,
        private readonly FetchActiveQuoteUseCase $fetchActiveQuoteUseCase,
        private readonly FetchActiveWordUseCase  $fetchActiveWordUseCase,
        private readonly array                   $StopIDs,
    )
    {
        parent::__construct($authenticationService, $csrfService, $logger);
    }

    /**
     * Renders display page
     */
    public function index(): void
    {
        $this->render('pages/display');
    }

    /**
     * @throws Exception
     */
    private function isModuleVisible(string $module): bool
    {
        $isModuleVisible = $this->moduleService->isVisible($module);
        if ($isModuleVisible) {
            return true;
        }
        return false;
    }

    public function getDepartures(): void
    {
        try {
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
            $this->sendError('Error while processing countdowns.', 500, []);
        }
    }

    public function getWeather(): void
    {
        try {
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
                ], 'No weather data available.');
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
                'Error fetching weather data.',
                500,
                    []
            );
        }
    }

    public function getEvents(): void
    {
        try {
            if (!$this->isModuleVisible('calendar')) {
                $this->sendSuccess([
                    'is_active' => false,
                    'events' => null
                    ]);
            }

            $events = $this->calendarService->getEvents();

            $eventsArray = [];

            foreach ($events->getItems() as $event) {
                $eventsArray[] = [
                    'summary' => $event->getSummary() === null ? "Wydarzenie bez tytułu" : $event->getSummary(),
                    'description' => $event->getDescription() === null ? "Wydarzenie bez opisu" : $event->getDescription(),
                    'start' => isset($event->getStart()->dateTime) === true ? new DateTime(($event->getStart()->dateTime))->format('H:i') : "Wydarzenie całodniowe",
                    'end' => isset($event->getEnd()->dateTime) === true ? new DateTime(($event->getEnd()->dateTime))->format('H:i') : null,
                ];
            }

            if (!empty($eventsArray)) {
                $this->sendSuccess([
                    'is_active' => true,
                    'events' => $eventsArray
                ]);
            } else {
                $this->sendSuccess([
                    'success' => false,
                    'is_active' => true
                ], 'No events found for provided today');

            }
        } catch (Exception) {
            $this->sendError('Error processing calendar data', 500, []);
        }
    }

    public function getQuote(): void
    {
        try {
            if (!$this->isModuleVisible('quote')) {
                $this->sendSuccess([
                    'is_active' => false,
                    'quote' => null
                ]);
            }

            $quote = $this->fetchActiveQuoteUseCase->execute();

            $this->sendSuccess([
                'is_active' => true,
                'quote' => [
                    'from' => $quote->author,
                    'quote' => $quote->quote,
                ]
            ]);
        } catch (Exception) {
            $this->sendError(
                'Error fetching quote data.',
                500,
                []
            );
        }
    }
    public function getWord(): void
    {
        try {
            if (!$this->isModuleVisible('word')) {
                $this->sendSuccess([
                    'is_active' => false,
                    'word' => null
                ]);
            }

            $word = $this->fetchActiveWordUseCase->execute();

            $this->sendSuccess([
                'is_active' => true,
                'word' => [
                    'word'          => $word->word,
                    'ipa'           => $word->ipa,
                    'definition'    => $word->definition,
                ]
            ]);
        } catch (Exception) {
            $this->sendError(
                'Error fetching word data.',
                500,
                []
            );
        }
    }
}