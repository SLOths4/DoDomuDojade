<?php

namespace App\Http\Controller;

use App\Application\Announcement\GetValidAnnouncementsUseCase;
use App\Application\Countdown\GetCurrentCountdownUseCase;
use App\Application\Module\IsModuleVisibleUseCase;
use App\Application\Quote\FetchActiveQuoteUseCase;
use App\Application\User\GetUserByIdUseCase;
use App\Application\Word\FetchActiveWordUseCase;
use App\Domain\Exception\DisplayException;
use App\Domain\Module\ModuleName;
use App\Http\Context\RequestContext;
use App\Infrastructure\Service\CalendarService;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\Service\TramService;
use App\Infrastructure\Service\WeatherService;
use App\Infrastructure\Trait\SendResponseTrait;
use App\Infrastructure\View\ViewRendererInterface;
use DateTime;
use DateTimeZone;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;

/**
 * Display controller - handles public display API endpoints
 * Returns JSON responses for frontend display module
 */
final class DisplayController extends BaseController
{
    use SendResponseTrait;

    public function __construct(
        readonly RequestContext $requestContext,
        readonly ViewRendererInterface $renderer,
        readonly FlashMessengerInterface $flash,
        private readonly LoggerInterface                $logger,
        private readonly WeatherService                 $weatherService,
        private readonly IsModuleVisibleUseCase         $isModuleVisibleUseCase,
        private readonly TramService                    $tramService,
        private readonly CalendarService                $calendarService,
        private readonly GetValidAnnouncementsUseCase   $getValidAnnouncementsUseCase,
        private readonly GetUserByIdUseCase             $getUserByIdUseCase,
        private readonly GetCurrentCountdownUseCase     $getCurrentCountdownUseCase,
        private readonly FetchActiveQuoteUseCase        $fetchActiveQuoteUseCase,
        private readonly FetchActiveWordUseCase         $fetchActiveWordUseCase,
        private readonly array                          $StopIDs,
    ){}

    /**
     * Render public display page
     */
    public function index(): void
    {
        $this->render('pages/display');
    }

    /**
     * Check if a module is visible/active
     *
     * @throws DisplayException
     * @throws Exception
     */
    private function isModuleVisible(ModuleName $module): bool
    {
        return $this->isModuleVisibleUseCase->execute($module);
    }

    /**
     * Get tram departures for configured stops
     *
     * @throws DisplayException
     */
    #[NoReturn]
    public function getDepartures(): void
    {
        if (!$this->isModuleVisible(ModuleName::tram)) {
            $this->sendSuccess([
                'is_active' => false,
                'departures' => null
            ]);
            exit;
        }

        $departures = [];
        foreach ($this->StopIDs as $stopId) {
            try {
                $stopDepartures = $this->tramService->getTimes($stopId);
            } catch (Exception $e) {
                $this->logger->warning("No departures found for stop", ['stopId' => $stopId]);
                continue;
            }

            if (!isset($stopDepartures['times']) || !is_array($stopDepartures['times'])) {
                $this->logger->warning("Invalid departure data format", ['stopId' => $stopId]);
                continue;
            }

            foreach ($stopDepartures['times'] as $departure) {
                $departures[] = [
                    'stopId' => $stopId,
                    'line' => (string)$departure['line'],
                    'minutes' => (int)$departure['minutes'],
                    'direction' => (string)$departure['direction'],
                ];
            }
        }

        usort($departures, static fn($a, $b) => $a['minutes'] <=> $b['minutes']);

        if (empty($departures)) {
            $this->sendSuccess([
                'is_active' => true,
                'departures' => []
            ]);
            exit;
        }

        $this->sendSuccess([
            'is_active' => true,
            'departures' => $departures
        ]);
        exit;
    }

    /**
     * Get valid announcements for display
     *
     * @throws DisplayException
     * @throws Exception
     */
    #[NoReturn]
    public function getAnnouncements(): void
    {
        if (!$this->isModuleVisible(ModuleName::announcement)) {
            $this->sendSuccess([
                'is_active' => false,
                'announcements' => null
            ]);
            exit;
        }

        $announcements = $this->getValidAnnouncementsUseCase->execute();

        if (empty($announcements)) {
            $this->sendSuccess([
                'is_active' => true,
                'announcements' => []
            ]);
            exit;
        }

        $response = [];
        foreach ($announcements as $announcement) {
            $author = 'Nieznany użytkownik';

            if (!is_null($announcement->userId)) {
                try {
                    $user = $this->getUserByIdUseCase->execute($announcement->userId);
                    $author = $user->username;
                } catch (Exception $e) {
                    $this->logger->warning("Failed to fetch announcement author", [
                        'userId' => $announcement->userId
                    ]);
                }
            }

            $response[] = [
                'title' => $announcement->title,
                'author' => $author,
                'text' => $announcement->text,
            ];
        }

        $this->sendSuccess([
            'is_active' => true,
            'announcements' => $response
        ]);
        exit;
    }

    /**
     * Get the current countdown
     *
     * @throws DisplayException
     * @throws Exception
     */
    #[NoReturn]
    public function getCountdown(): void
    {
        if (!$this->isModuleVisible(ModuleName::countdown)) {
            $this->sendSuccess([
                'is_active' => false,
                'title' => null,
                'count_to' => null
            ]);
            exit;
        }

        $currentCountdown = $this->getCurrentCountdownUseCase->execute();

        if (!$currentCountdown) {
            $this->sendSuccess([
                'is_active' => true,
                'title' => null,
                'count_to' => null
            ]);
            exit;
        }

        // ✅ Convert to Warsaw timezone and Unix timestamp
        $dt = $currentCountdown->countTo->setTimezone(new DateTimeZone('Europe/Warsaw'));

        $this->sendSuccess([
            'is_active' => true,
            'title' => $currentCountdown->title,
            'count_to' => $dt->getTimestamp()
        ]);
        exit;
    }

    /**
     * Get current weather data from multiple sources
     *
     * @throws DisplayException
     */
    #[NoReturn]
    public function getWeather(): void
    {
        if (!$this->isModuleVisible(ModuleName::weather)) {
            $this->sendSuccess([
                'is_active' => false,
                'weather' => null
            ]);
            exit;
        }

        try {
            $weatherData = $this->weatherService->getWeather();
        } catch (Exception $e) {
            $this->logger->error("Failed to fetch weather data", ['error' => $e->getMessage()]);
            throw DisplayException::failedToFetchWeather();
        }

        if (empty($weatherData)) {
            $this->sendSuccess([
                'is_active' => true,
                'weather' => null
            ]);
            exit;
        }

        // ✅ Format weather response
        $this->sendSuccess([
            'is_active' => true,
            'weather' => [
                'temperature' => (string)($weatherData['imgw_temperature'] ?? 'N/A'),
                'pressure' => (string)($weatherData['imgw_pressure'] ?? 'N/A'),
                'airlyAdvice' => (string)($weatherData['airly_index_advice'] ?? 'N/A'),
                'airlyDescription' => (string)($weatherData['airly_index_description'] ?? 'N/A'),
                'airlyColour' => (string)($weatherData['airly_index_colour'] ?? 'N/A')
            ]
        ]);
        exit;
    }

    public function getEvents(): void
    {
        try {
            if (!$this->isModuleVisible(ModuleName::calendar)) {
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

    /**
     * Get active quote of the day
     *
     * @throws DisplayException
     */
    #[NoReturn]
    public function getQuote(): void
    {
        if (!$this->isModuleVisible(ModuleName::quote)) {
            $this->sendSuccess([
                'is_active' => false,
                'quote' => null
            ]);
            exit;
        }

        try {
            $quote = $this->fetchActiveQuoteUseCase->execute();
        } catch (Exception $e) {
            $this->logger->error("Failed to fetch quote", ['error' => $e->getMessage()]);
            throw DisplayException::failedToFetchQuote();
        }

        if (!$quote) {
            $this->sendSuccess([
                'is_active' => true,
                'quote' => null
            ]);
            exit;
        }

        $this->sendSuccess([
            'is_active' => true,
            'quote' => [
                'from' => $quote->author,
                'quote' => $quote->quote,
            ]
        ]);
        exit;
    }

    /**
     * Get active word of the day
     *
     * @throws DisplayException
     */
    #[NoReturn]
    public function getWord(): void
    {
        if (!$this->isModuleVisible(ModuleName::word)) {
            $this->sendSuccess([
                'is_active' => false,
                'word' => null
            ]);
            exit;
        }

        try {
            $word = $this->fetchActiveWordUseCase->execute();
        } catch (Exception $e) {
            $this->logger->error("Failed to fetch word", ['error' => $e->getMessage()]);
            throw DisplayException::failedToFetchWord();
        }

        if (!$word) {
            $this->sendSuccess([
                'is_active' => true,
                'word' => null
            ]);
            exit;
        }

        $this->sendSuccess([
            'is_active' => true,
            'word' => [
                'word' => $word->word,
                'ipa' => $word->ipa,
                'definition' => $word->definition,
            ]
        ]);
        exit;
    }
}
