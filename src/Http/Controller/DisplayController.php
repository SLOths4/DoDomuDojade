<?php

namespace App\Http\Controller;

use App\Application\UseCase\Countdown\GetCurrentCountdownUseCase;
use App\Application\UseCase\Module\IsModuleVisibleUseCase;
use App\Application\UseCase\Announcement\GetValidAnnouncementsUseCase;
use App\Application\UseCase\Quote\FetchActiveQuoteUseCase;
use App\Application\UseCase\Word\FetchActiveWordUseCase;
use App\Application\UseCase\User\GetUserByIdUseCase;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Security\CsrfService;
use App\Infrastructure\Service\TramService;
use App\Infrastructure\Service\WeatherService;
use App\Infrastructure\Trait\SendResponseTrait;
use DateTimeZone;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;

class DisplayController extends BaseController
{
    use SendResponseTrait;
    public function __construct(
        AuthenticationService                    $authenticationService,
        CsrfService                              $csrfService,
        LoggerInterface                          $logger,
        private readonly WeatherService          $weatherService,
        private readonly IsModuleVisibleUseCase  $isModuleVisibleUseCase,
        private readonly TramService             $tramService,
        private readonly GetValidAnnouncementsUseCase $getValidAnnouncementsUseCase,
        private readonly GetUserByIdUseCase      $getUserByIdUseCase,
        private readonly GetCurrentCountdownUseCase $getCurrentCountdownUseCase,
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
        return $this->isModuleVisibleUseCase->execute($module);
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

    #[NoReturn]
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

            $announcements = $this->getValidAnnouncementsUseCase->execute();

            $response = [];
            foreach ($announcements as $announcement) {

                if (!is_null($announcement->userId)) {
                    $user = $this->getUserByIdUseCase->execute($announcement->userId);
                }

                $author = $user->username ?? 'Nieznany użytkownik';

                $response[] = [
                    'title' => $announcement->title,
                    'author' => $author,
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

            $currentCountdown = $this->getCurrentCountdownUseCase->execute();

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

            if ($quote) {
                $this->sendSuccess([
                    'is_active' => true,
                    'quote' => [
                        'from' => $quote->author,
                        'quote' => $quote->quote,
                    ]
                ]);
            } else {
                $this->sendSuccess([
                    'success' => true,
                    'quote' => null
                ], 'No quote available.');
            }
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

            if ($word) {
                $this->sendSuccess([
                    'is_active' => true,
                    'word' => [
                        'word'          => $word->word,
                        'ipa'           => $word->ipa,
                        'definition'    => $word->definition,
                    ]
                ]);
            } else {
                $this->sendSuccess([
                    'success' => true,
                    'word' => null
                ], 'No word available.');
            }
        } catch (Exception) {
            $this->sendError(
                'Error fetching word data.',
                500,
                []
            );
        }
    }
}