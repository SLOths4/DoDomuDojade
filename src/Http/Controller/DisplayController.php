<?php

namespace App\Http\Controller;

use App\Application\Display\GetDeparturesUseCase;
use App\Application\Display\GetDisplayAnnouncementsUseCase;
use App\Application\Display\GetDisplayCountdownUseCase;
use App\Application\Display\GetDisplayEventsUseCase;
use App\Application\Display\GetDisplayQuoteUseCase;
use App\Application\Display\GetDisplayWeatherUseCase;
use App\Application\Display\GetDisplayWordUseCase;
use App\Application\Module\IsModuleVisibleUseCase;
use App\Domain\Module\ModuleName;
use App\Http\Context\RequestContext;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\Trait\SendResponseTrait;
use App\Infrastructure\View\ViewRendererInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Exception;

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
        private readonly IsModuleVisibleUseCase         $isModuleVisibleUseCase,
        private readonly GetDeparturesUseCase           $getDeparturesUseCase,
        private readonly GetDisplayAnnouncementsUseCase $getDisplayAnnouncementsUseCase,
        private readonly GetDisplayCountdownUseCase     $getDisplayCountdownUseCase,
        private readonly GetDisplayWeatherUseCase       $getDisplayWeatherUseCase,
        private readonly GetDisplayEventsUseCase        $getDisplayEventsUseCase,
        private readonly GetDisplayQuoteUseCase         $getDisplayQuoteUseCase,
        private readonly GetDisplayWordUseCase          $getDisplayWordUseCase,
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
     * Get tram departures for configured stops
     */
    public function getDepartures(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::tram)) {
            return $this->sendSuccess([
                'is_active' => false,
                'departures' => null
            ]);
        }

        $departures = $this->getDeparturesUseCase->execute($this->StopIDs);

        return $this->sendSuccess([
            'is_active' => true,
            'departures' => $departures
        ]);
    }

    /**
     * Get valid announcements for display
     */
    public function getAnnouncements(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::announcement)) {
            return $this->sendSuccess([
                'is_active' => false,
                'announcements' => null
            ]);
        }

        $announcements = $this->getDisplayAnnouncementsUseCase->execute();

        return $this->sendSuccess([
            'is_active' => true,
            'announcements' => $announcements
        ]);
    }

    /**
     * Get the current countdown
     */
    public function getCountdown(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::countdown)) {
            return $this->sendSuccess([
                'is_active' => false,
                'title' => null,
                'count_to' => null
            ]);
        }

        $data = $this->getDisplayCountdownUseCase->execute();

        return $this->sendSuccess([
            'is_active' => true,
            'title' => $data['title'] ?? null,
            'count_to' => $data['count_to'] ?? null
        ]);
    }

    /**
     * Get current weather data from multiple sources
     */
    public function getWeather(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::weather)) {
            return $this->sendSuccess([
                'is_active' => false,
                'weather' => null
            ]);
        }

        $weather = $this->getDisplayWeatherUseCase->execute();

        return $this->sendSuccess([
            'is_active' => true,
            'weather' => $weather
        ]);
    }

    public function getEvents(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::calendar)) {
            return $this->sendSuccess([
                'is_active' => false,
                'events' => null
            ]);
        }

        $eventsArray = $this->getDisplayEventsUseCase->execute();

        if (!empty($eventsArray)) {
            return $this->sendSuccess([
                'is_active' => true,
                'events' => $eventsArray
            ]);
        }

        return $this->sendSuccess([
            'success' => false,
            'is_active' => true
        ], 'No events found or error processing data');
    }

    /**
     * Get active quote of the day
     */
    public function getQuote(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::quote)) {
            return $this->sendSuccess([
                'is_active' => false,
                'quote' => null
            ]);
        }

        $quote = $this->getDisplayQuoteUseCase->execute();

        return $this->sendSuccess([
            'is_active' => true,
            'quote' => $quote
        ]);
    }

    /**
     * Get active word of the day
     */
    public function getWord(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::word)) {
            return $this->sendSuccess([
                'is_active' => false,
                'word' => null
            ]);
        }

        $word = $this->getDisplayWordUseCase->execute();

        return $this->sendSuccess([
            'is_active' => true,
            'word' => $word
        ]);
    }
}
