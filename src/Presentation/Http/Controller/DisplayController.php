<?php

namespace App\Presentation\Http\Controller;

use App\Application\Display\GetDeparturesUseCase;
use App\Application\Display\GetDisplayAnnouncementsUseCase;
use App\Application\Display\GetDisplayCountdownUseCase;
use App\Application\Display\GetDisplayEventsUseCase;
use App\Application\Display\GetDisplayQuoteUseCase;
use App\Application\Display\GetDisplayWeatherUseCase;
use App\Application\Display\GetDisplayWordUseCase;
use App\Application\Module\UseCase\IsModuleVisibleUseCase;
use App\Domain\Module\ModuleName;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\ViewRendererInterface;
use App\Presentation\View\TemplateNames;
use Psr\Http\Message\ResponseInterface;

final class DisplayController extends BaseController
{
    public function __construct(
        RequestContext $requestContext,
        ViewRendererInterface $renderer,
        private readonly IsModuleVisibleUseCase $isModuleVisibleUseCase,
        private readonly GetDeparturesUseCase $getDeparturesUseCase,
        private readonly GetDisplayAnnouncementsUseCase $getDisplayAnnouncementsUseCase,
        private readonly GetDisplayCountdownUseCase $getDisplayCountdownUseCase,
        private readonly GetDisplayWeatherUseCase $getDisplayWeatherUseCase,
        private readonly GetDisplayEventsUseCase $getDisplayEventsUseCase,
        private readonly GetDisplayQuoteUseCase $getDisplayQuoteUseCase,
        private readonly GetDisplayWordUseCase $getDisplayWordUseCase,
        private readonly array $StopIDs,
    ) {
        parent::__construct($requestContext, $renderer);
    }

    public function index(): ResponseInterface
    {
        return $this->render(TemplateNames::DISPLAY->value);
    }

    public function getDepartures(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::tram)) {
            return $this->jsonResponse(200, [
                'is_active' => false,
                'departures' => null,
            ]);
        }

        $departures = $this->getDeparturesUseCase->execute($this->StopIDs);

        return $this->jsonResponse(200, [
            'is_active' => true,
            'departures' => $departures,
        ]);
    }

    public function getAnnouncements(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::announcement)) {
            return $this->jsonResponse(200, [
                'is_active' => false,
                'announcements' => null,
            ]);
        }

        $announcements = $this->getDisplayAnnouncementsUseCase->execute();

        return $this->jsonResponse(200, [
            'is_active' => true,
            'announcements' => $announcements,
        ]);
    }

    public function getCountdown(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::countdown)) {
            return $this->jsonResponse(200, [
                'is_active' => false,
                'title' => null,
                'count_to' => null,
            ]);
        }

        $data = $this->getDisplayCountdownUseCase->execute();

        return $this->jsonResponse(200, [
            'is_active' => true,
            'title' => $data['title'] ?? null,
            'count_to' => $data['count_to'] ?? null,
        ]);
    }

    public function getWeather(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::weather)) {
            return $this->jsonResponse(200, [
                'is_active' => false,
                'weather' => null,
            ]);
        }

        $weather = $this->getDisplayWeatherUseCase->execute();

        return $this->jsonResponse(200, [
            'is_active' => true,
            'weather' => $weather,
        ]);
    }

    public function getEvents(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::calendar)) {
            return $this->jsonResponse(200, [
                'is_active' => false,
                'events' => null,
            ]);
        }

        $eventsArray = $this->getDisplayEventsUseCase->execute();

        if (!empty($eventsArray)) {
            return $this->jsonResponse(200, [
                'is_active' => true,
                'events' => $eventsArray,
            ]);
        }

        return $this->jsonResponse(200, [
            'is_active' => true,
            'events' => null,
        ]);
    }

    public function getQuote(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::quote)) {
            return $this->jsonResponse(200, [
                'is_active' => false,
                'quote' => null,
            ]);
        }

        $quote = $this->getDisplayQuoteUseCase->execute();

        return $this->jsonResponse(200, [
            'is_active' => true,
            'quote' => $quote,
        ]);
    }

    public function getWord(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::word)) {
            return $this->jsonResponse(200, [
                'is_active' => false,
                'word' => null,
            ]);
        }

        $word = $this->getDisplayWordUseCase->execute();

        return $this->jsonResponse(200, [
            'is_active' => true,
            'word' => $word,
        ]);
    }
}
