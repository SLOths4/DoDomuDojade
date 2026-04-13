<?php
declare(strict_types=1);

namespace App\Infrastructure\Container\Providers;

use App\Application\Display\GetDeparturesUseCase;
use App\Application\Display\GetDisplayAnnouncementsUseCase;
use App\Application\Display\GetDisplayCountdownUseCase;
use App\Application\Display\GetDisplayEventsUseCase;
use App\Application\Display\GetDisplayQuoteUseCase;
use App\Application\Display\GetDisplayWeatherUseCase;
use App\Application\Display\GetDisplayWordUseCase;
use App\Application\Module\UseCase\GetAllModulesUseCase;
use App\Application\Module\UseCase\IsModuleVisibleUseCase;
use App\Application\User\UseCase\GetAllUsersUseCase;
use App\Application\User\UseCase\GetUserByIdUseCase;
use App\Application\Quote\FetchActiveQuoteUseCase;
use App\Application\Countdown\UseCase\GetAllCountdownsUseCase;
use App\Application\Countdown\UseCase\GetCurrentCountdownUseCase;
use App\Application\Announcement\UseCase\GetAllAnnouncementsUseCase;
use App\Application\Announcement\UseCase\GetValidAnnouncementsUseCase;
use App\Application\Word\FetchActiveWordUseCase;
use App\Domain\Weather\WeatherRepositoryInterface;
use App\Infrastructure\Configuration\Config;
use App\Infrastructure\Container;
use App\Infrastructure\ExternalApi\Calendar\CalendarService;
use App\Infrastructure\ExternalApi\Tram\TramService;
use App\Infrastructure\ExternalApi\Weather\WeatherService;
use App\Infrastructure\Service\FlashMessengerService;
use App\Infrastructure\Twig\TwigRenderer;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Controller\DisplayController;
use App\Presentation\Http\Controller\ErrorController;
use App\Presentation\Http\Controller\PanelController;
use App\Presentation\Http\Presenter\AnnouncementPresenter;
use App\Presentation\Http\Shared\Translator;
use Psr\Log\LoggerInterface;

final class DisplayProvider implements ServiceProviderInterface
{
    public function register(Container $c): void
    {
        $c->set(GetDeparturesUseCase::class, fn(Container $c) => new GetDeparturesUseCase(
            $c->get(TramService::class),
            $c->get(LoggerInterface::class),
        ));
        $c->set(GetDisplayAnnouncementsUseCase::class, fn(Container $c) => new GetDisplayAnnouncementsUseCase(
            $c->get(GetValidAnnouncementsUseCase::class),
            $c->get(GetUserByIdUseCase::class),
            $c->get(LoggerInterface::class),
        ));
        $c->set(GetDisplayCountdownUseCase::class, fn(Container $c) => new GetDisplayCountdownUseCase(
            $c->get(GetCurrentCountdownUseCase::class),
        ));
        $c->set(GetDisplayWeatherUseCase::class, fn(Container $c) => new GetDisplayWeatherUseCase(
            $c->get(WeatherService::class),
            $c->get(WeatherRepositoryInterface::class),
            $c->get(LoggerInterface::class),
            $c->get(Config::class)->weatherCacheTtlSeconds,
        ));
        $c->set(GetDisplayEventsUseCase::class, fn(Container $c) => new GetDisplayEventsUseCase(
            $c->get(CalendarService::class),
            $c->get(LoggerInterface::class),
        ));
        $c->set(GetDisplayQuoteUseCase::class, fn(Container $c) => new GetDisplayQuoteUseCase(
            $c->get(FetchActiveQuoteUseCase::class),
            $c->get(LoggerInterface::class),
        ));
        $c->set(GetDisplayWordUseCase::class, fn(Container $c) => new GetDisplayWordUseCase(
            $c->get(FetchActiveWordUseCase::class),
            $c->get(LoggerInterface::class),
        ));

        $c->set(ErrorController::class, fn(Container $c) => new ErrorController(
            $c->get(RequestContext::class),
            $c->get(TwigRenderer::class),
            $c->get(FlashMessengerService::class),
        ));

        $c->set(DisplayController::class, fn(Container $c) => new DisplayController(
            $c->get(RequestContext::class),
            $c->get(TwigRenderer::class),
            $c->get(IsModuleVisibleUseCase::class),
            $c->get(GetDeparturesUseCase::class),
            $c->get(GetDisplayAnnouncementsUseCase::class),
            $c->get(GetDisplayCountdownUseCase::class),
            $c->get(GetDisplayWeatherUseCase::class),
            $c->get(GetDisplayEventsUseCase::class),
            $c->get(GetDisplayQuoteUseCase::class),
            $c->get(GetDisplayWordUseCase::class),
            $c->get(Config::class)->stopID,
        ));

        $c->set(PanelController::class, fn(Container $c) => new PanelController(
            $c->get(RequestContext::class),
            $c->get(TwigRenderer::class),
            $c->get(LoggerInterface::class),
            $c->get(GetAllModulesUseCase::class),
            $c->get(GetAllUsersUseCase::class),
            $c->get(GetAllCountdownsUseCase::class),
            $c->get(GetAllAnnouncementsUseCase::class),
            $c->get(Translator::class),
            $c->get(AnnouncementPresenter::class),
        ));
    }
}
