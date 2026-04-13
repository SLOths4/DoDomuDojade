<?php
declare(strict_types=1);

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
use Random\RandomException;

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
            return $this->inactiveResponse('Moduł odjazdów jest wyłączony.');
        }

        $departures = $this->getDeparturesUseCase->execute($this->StopIDs);

        if ($this->isEmptyData($departures)) {
            return $this->emptyResponse('Brak dostępnych odjazdów.');
        }

        return $this->activeResponse($departures);
    }

    public function getAnnouncements(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::announcement)) {
            return $this->inactiveResponse('Moduł ogłoszeń jest wyłączony.');
        }

        $announcements = $this->getDisplayAnnouncementsUseCase->execute();

        if ($this->isEmptyData($announcements)) {
            return $this->emptyResponse('Brak dostępnych ogłoszeń.');
        }

        return $this->activeResponse($announcements);
    }

    public function getCountdown(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::countdown)) {
            return $this->inactiveResponse('Moduł odliczania jest wyłączony.');
        }

        $countdown = $this->getDisplayCountdownUseCase->execute();

        if ($this->isEmptyData($countdown)) {
            return $this->emptyResponse('Brak dostępnego odliczania.');
        }

        return $this->activeResponse($countdown);
    }

    public function getWeather(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::weather)) {
            return $this->inactiveResponse('Moduł pogody jest wyłączony.');
        }

        $weather = $this->getDisplayWeatherUseCase->execute();

        if ($this->isEmptyData($weather)) {
            return $this->emptyResponse('Brak dostępnych odczytów pogodowych.');
        }

        return $this->activeResponse($weather);
    }

    public function getEvents(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::calendar)) {
            return $this->inactiveResponse('Moduł wydarzeń jest wyłączony.');
        }

        $eventsArray = $this->getDisplayEventsUseCase->execute();

        if ($this->isEmptyData($eventsArray)) {
            return $this->emptyResponse('Brak dostępnych wydarzeń.');
        }

        return $this->activeResponse($eventsArray);
    }

    public function getQuote(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::quote)) {
            return $this->inactiveResponse('Moduł cytatu jest wyłączony.');
        }

        $quote = $this->getDisplayQuoteUseCase->execute();

        if ($this->isEmptyData($quote)) {
            return $this->emptyResponse('Brak dostępnego cytatu.');
        }

        return $this->activeResponse($quote);
    }

    public function getWord(): ResponseInterface
    {
        if (!$this->isModuleVisibleUseCase->execute(ModuleName::word)) {
            return $this->inactiveResponse('Moduł słowa dnia jest wyłączony.');
        }

        $word = $this->getDisplayWordUseCase->execute();

        if ($this->isEmptyData($word)) {
            return $this->emptyResponse('Brak dostępnego słowa dnia.');
        }

        return $this->activeResponse($word);
    }

    private function activeResponse(mixed $data, ?string $message = null): ResponseInterface
    {
        return $this->displayResponse('active', $data, $message);
    }

    private function inactiveResponse(string $message): ResponseInterface
    {
        return $this->displayResponse('inactive', null, $message);
    }

    private function emptyResponse(string $message): ResponseInterface
    {
        return $this->displayResponse('empty', null, $message);
    }

    private function displayResponse(string $status, mixed $data, ?string $message = null): ResponseInterface
    {
        $requestId = $this->resolveRequestId();

        return $this->jsonResponse(200, [
            'status' => $status,
            'data' => $data,
            'message' => $message,
            'request_id' => $requestId,
        ], [
            'X-Request-Id' => $requestId,
        ]);
    }

    private function isEmptyData(mixed $data): bool
    {
        if ($data === null) {
            return true;
        }

        if (is_array($data)) {
            return $data === [];
        }

        if (is_string($data)) {
            return trim($data) === '';
        }

        return false;
    }

    private function resolveRequestId(): string
    {
        $requestId = $this->requestContext->get('request_id');
        if (is_string($requestId) && $requestId !== '') {
            return $requestId;
        }

        try {
            $generatedRequestId = bin2hex(random_bytes(8));
        } catch (RandomException) {
            $generatedRequestId = uniqid('', true);
        }

        $this->requestContext->set('request_id', $generatedRequestId);

        return $generatedRequestId;
    }
}
