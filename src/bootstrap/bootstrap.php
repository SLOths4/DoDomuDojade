<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__. '/../../../'));
$dotenv->load();

use App\Application\UseCase\Announcement\DeleteRejectedSinceAnnouncementUseCase;
use App\Application\UseCase\Quote\FetchActiveQuoteUseCase;
use App\Application\UseCase\Quote\FetchQuoteUseCase;
use App\Console\CommandRegistry;
use App\Console\Commands\AnnouncementRejectedDeleteCommand;
use App\Console\Commands\QuoteFetchCommand;
use App\Console\Commands\WordFetchCommand;
use App\Console\Kernel;
use App\Http\Context\LocaleContext;
use App\Http\Context\RequestContext;
use App\Infrastructure\Factory\TwigFactory;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Helper\CountdownValidationHelper;
use App\Infrastructure\Helper\ModuleValidationHelper;
use App\Infrastructure\Repository\QuoteRepository;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\Service\FlashMessengerService;
use App\Infrastructure\Service\QuoteApiService;
use App\Application\UseCase\Word\FetchActiveWordUseCase;
use App\Application\UseCase\Word\FetchWordUseCase;
use App\Infrastructure\Repository\WordRepository;
use App\Infrastructure\Service\WordApiService;
use App\Infrastructure\Service\CalendarService;
use App\Infrastructure\Translation\LanguageTranslator;
use App\Infrastructure\Translation\Translator;
use App\Infrastructure\View\TwigRenderer;
use App\Infrastructure\View\ViewRendererInterface;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\Announcement\CreateAnnouncementUseCase;
use App\Application\UseCase\Announcement\DeleteAnnouncementUseCase;
use App\Application\UseCase\Announcement\EditAnnouncementUseCase;
use App\Application\UseCase\Announcement\GetAllAnnouncementsUseCase;
use App\Application\UseCase\Announcement\GetValidAnnouncementsUseCase;
use App\Application\UseCase\Countdown\CreateCountdownUseCase;
use App\Application\UseCase\Countdown\DeleteCountdownUseCase;
use App\Application\UseCase\Countdown\GetAllCountdownsUseCase;
use App\Application\UseCase\Countdown\GetCountdownByIdUseCase;
use App\Application\UseCase\Countdown\GetCurrentCountdownUseCase;
use App\Application\UseCase\Countdown\UpdateCountdownUseCase;
use App\Application\UseCase\Module\GetAllModulesUseCase;
use App\Application\UseCase\Module\GetModuleByIdUseCase;
use App\Application\UseCase\Module\IsModuleVisibleUseCase;
use App\Application\UseCase\Module\ToggleModuleUseCase;
use App\Application\UseCase\Module\UpdateModuleUseCase;
use App\Infrastructure\Service\TramService;
use App\Application\UseCase\User\CreateUserUseCase;
use App\Application\UseCase\User\DeleteUserUseCase;
use App\Application\UseCase\User\GetAllUsersUseCase;
use App\Application\UseCase\User\GetUserByIdUseCase;
use App\Application\UseCase\User\GetUserByUsernameUseCase;
use App\Application\UseCase\User\UpdateUserUseCase;
use App\Application\UseCase\User\ChangePasswordUseCase;
use App\Infrastructure\Service\WeatherService;
use App\config\Config;
use App\Http\Controller\DisplayController;
use App\Http\Controller\ErrorController;
use App\Http\Controller\PanelController;
use App\Infrastructure\Container;
use App\Infrastructure\Factory\LoggerFactory;
use App\Infrastructure\Factory\PDOFactory;
use App\Infrastructure\Helper\DatabaseHelper;
use App\Infrastructure\Repository\AnnouncementRepository;
use App\Infrastructure\Repository\CountdownRepository;
use App\Infrastructure\Repository\ModuleRepository;
use App\Infrastructure\Repository\UserRepository;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Service\CsrfTokenService;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

$container = new Container();

// Config
$container->set(Config::class, fn() => Config::fromEnv());

// Logger
$container->set(LoggerInterface::class, function(Container $c) {
    $cfg = $c->get(Config::class);
    return LoggerFactory::create(
        logsDirectory: $cfg->loggingDirectoryPath,
        channel: $cfg->loggingChannelName,
        level: $cfg->loggingLevel,
    );
});

// PDO
$container->set(PDO::class, fn(Container $c) => $c->get(PDOFactory::class)->create($c->get(Config::class)));

// DatabaseHelper
$container->set(DatabaseHelper::class, fn() => new DatabaseHelper($container->get(PDO::class), $container->get(LoggerInterface::class)));

// HTTP client
$container->set(HttpClientInterface::class, fn() => HttpClient::create());

// AuthenticationService
$container->set(AuthenticationService::class, fn() => new AuthenticationService($container->get(UserRepository::class)));

// CsrfTokenService
$container->set(CsrfTokenService::class, fn() => new CsrfTokenService);

$container->set(LocaleContext::class, function () {
    return new LocaleContext();
});

$container->set(Translator::class, function ($c) {
    return new LanguageTranslator(
        $c->get(LocaleContext::class),
    );
});

$container->set(CommandRegistry::class, function ($c) {
    $registry = new CommandRegistry();

    $registry->register($c->get(QuoteFetchCommand::class));
    $registry->register($c->get(WordFetchCommand::class));
    $registry->register($c->get(AnnouncementRejectedDeleteCommand::class));

    return $registry;
});

$container->set(Kernel::class, function ($c) {
    return new Kernel($c->get(CommandRegistry::class));
});

$container->set(Environment::class, fn(Container $c) => $c->get(TwigFactory::class)->create($c->get(Config::class)));

$container->set(ViewRendererInterface::class, function (Container $c) {
    return new TwigRenderer(
      $c->get(Environment::class),
      $c->get(RequestContext::class),
      $c->get(LocaleContext::class),
      $c->get(FlashMessengerService::class),
      $c->get(Translator::class),
    );
});

$container->set(FlashMessengerInterface::class, function (Container $c) {
    return new FlashMessengerService();
});

// ANNOUNCEMENTS
// AnnouncementsRepository
$container->set(AnnouncementRepository::class, function (Container $c): AnnouncementRepository {
    $cfg = $c->get(Config::class);
    return new AnnouncementRepository(
        $c->get(DatabaseHelper::class),
        $cfg->announcementTableName,
        $cfg->announcementDateFormat,
    );
});

// Announcement Use Cases
$container->set(CreateAnnouncementUseCase::class, fn(Container $c) => new CreateAnnouncementUseCase($c->get(AnnouncementRepository::class), $c->get(LoggerInterface::class), $c->get(AnnouncementValidationHelper::class) ));
$container->set(DeleteAnnouncementUseCase::class, fn(Container $c) => new DeleteAnnouncementUseCase($c->get(AnnouncementRepository::class), $c->get(LoggerInterface::class), $c->get(AnnouncementValidationHelper::class) ));
$container->set(DeleteRejectedSinceAnnouncementUseCase::class, fn(Container $c) => new DeleteRejectedSinceAnnouncementUseCase($c->get(AnnouncementRepository::class), $c->get(LoggerInterface::class)));
$container->set(EditAnnouncementUseCase::class, fn(Container $c) => new EditAnnouncementUseCase($c->get(AnnouncementRepository::class), $c->get(LoggerInterface::class), $c->get(AnnouncementValidationHelper::class) ));
$container->set(GetAllAnnouncementsUseCase::class, fn(Container $c) => new GetAllAnnouncementsUseCase($c->get(AnnouncementRepository::class), $c->get(LoggerInterface::class)));
$container->set(GetValidAnnouncementsUseCase::class, fn(Container $c) => new GetValidAnnouncementsUseCase($c->get(AnnouncementRepository::class), $c->get(LoggerInterface::class)));

// USERS
// UserRepository
$container->set(UserRepository::class, function (Container $c): UserRepository {
    $cfg = $c->get(Config::class);
    return new UserRepository(
        $c->get(DatabaseHelper::class),
        $cfg->userTableName,
        $cfg->userDateFormat,
    );
});

// User Use Cases
$container->set(CreateUserUseCase::class, function(Container $c) {
    $cfg = $c->get(Config::class);
    return new CreateUserUseCase(
        $c->get(UserRepository::class),
        $c->get(LoggerInterface::class),
        $cfg->maxUsernameLength,
        $cfg->minPasswordLength
    );
});
$container->set(DeleteUserUseCase::class, fn(Container $c) => new DeleteUserUseCase($c->get(UserRepository::class), $c->get(LoggerInterface::class)));
$container->set(GetAllUsersUseCase::class, fn(Container $c) => new GetAllUsersUseCase($c->get(UserRepository::class), $c->get(LoggerInterface::class)));
$container->set(GetUserByIdUseCase::class, fn(Container $c) => new GetUserByIdUseCase($c->get(UserRepository::class), $c->get(LoggerInterface::class)));
$container->set(GetUserByUsernameUseCase::class, fn(Container $c) => new GetUserByUsernameUseCase($c->get(UserRepository::class), $c->get(LoggerInterface::class)));
$container->set(UpdateUserUseCase::class, function(Container $c) {
    $cfg = $c->get(Config::class);
    return new UpdateUserUseCase(
        $c->get(UserRepository::class),
        $c->get(LoggerInterface::class),
        $cfg->maxUsernameLength,
        $cfg->minPasswordLength
    );
});
$container->set(ChangePasswordUseCase::class, function(Container $c) {
    $cfg = $c->get(Config::class);
    return new ChangePasswordUseCase(
        $c->get(UserRepository::class),
        $c->get(LoggerInterface::class),
        $cfg->minPasswordLength
    );
});

// MODULES
// ModuleRepository
$container->set(ModuleRepository::class, function (Container $c): ModuleRepository {
    $cfg = $c->get(Config::class);
    return new ModuleRepository(
        $c->get(DatabaseHelper::class),
        $cfg->moduleTableName,
        $cfg->moduleDateFormat,
    );
});

// Module Use Cases
$container->set(GetAllModulesUseCase::class, fn(Container $c) => new GetAllModulesUseCase($c->get(ModuleRepository::class), $c->get(LoggerInterface::class)));
$container->set(GetModuleByIdUseCase::class, fn(Container $c) => new GetModuleByIdUseCase($c->get(ModuleRepository::class), $c->get(LoggerInterface::class), $c->get(ModuleValidationHelper::class) ));
$container->set(IsModuleVisibleUseCase::class, function(Container $c) {
    return new IsModuleVisibleUseCase(
        $c->get(ModuleRepository::class),
        $c->get(LoggerInterface::class),
    );
});
$container->set(ToggleModuleUseCase::class, fn(Container $c) => new ToggleModuleUseCase($c->get(ModuleRepository::class), $c->get(LoggerInterface::class), $c->get(ModuleValidationHelper::class) ));
$container->set(UpdateModuleUseCase::class, function(Container $c) {
    return new UpdateModuleUseCase(
        $c->get(ModuleRepository::class),
        $c->get(LoggerInterface::class),
        $c->get(ModuleValidationHelper::class),
    );
});

// TRAMS
// TramService
$container->set(TramService::class, function (Container $c): TramService {
    $cfg = $c->get(Config::class);
    return new TramService(
        $c->get(LoggerInterface::class),
        $c->get(HttpClientInterface::class),
        $cfg->tramUrl,
    );
});

// COUNTDOWNS
// CountdownRepository
$container->set(CountdownRepository::class, function (Container $c): CountdownRepository {
    $cfg = $c->get(Config::class);
    return new CountdownRepository(
        $c->get(DatabaseHelper::class),
        $cfg->countdownTableName,
        $cfg->countdownDateFormat,
    );
});

// Countdown Use Cases
$container->set(CreateCountdownUseCase::class, function(Container $c) {
    return new CreateCountdownUseCase(
        $c->get(CountdownRepository::class),
        $c->get(LoggerInterface::class),
        $c->get(CountdownValidationHelper::class),
    );
});
$container->set(DeleteCountdownUseCase::class, fn(Container $c) => new DeleteCountdownUseCase($c->get(CountdownRepository::class), $c->get(LoggerInterface::class), $c->get(CountdownValidationHelper::class) ));
$container->set(GetAllCountdownsUseCase::class, fn(Container $c) => new GetAllCountdownsUseCase($c->get(CountdownRepository::class), $c->get(LoggerInterface::class)));
$container->set(GetCountdownByIdUseCase::class, fn(Container $c) => new GetCountdownByIdUseCase($c->get(CountdownRepository::class), $c->get(LoggerInterface::class), $c->get(CountdownValidationHelper::class) ));
$container->set(GetCurrentCountdownUseCase::class, fn(Container $c) => new GetCurrentCountdownUseCase($c->get(CountdownRepository::class), $c->get(LoggerInterface::class)));
$container->set(UpdateCountdownUseCase::class, function(Container $c) {
    return new UpdateCountdownUseCase(
        $c->get(CountdownRepository::class),
        $c->get(LoggerInterface::class),
        $c->get(CountdownValidationHelper::class),
    );
});

// WEATHER
// WeatherService
$container->set(WeatherService::class, function (Container $c): WeatherService {
    $cfg = $c->get(Config::class);
    return new WeatherService(
        $c->get(LoggerInterface::class),
        $c->get(HttpClientInterface::class),
        $cfg->imgwWeatherUrl,
        $cfg->airlyEndpoint,
        $cfg->airlyApiKey,
        $cfg->airlyLocationId,
    );
});

// QuoteApiService
$container->set(QuoteApiService::class, function (Container $c): QuoteApiService {
    $cfg = $c->get(Config::class);
    return new QuoteApiService(
        $c->get(LoggerInterface::class),
        $c->get(HttpClientInterface::class),
        $cfg->quoteApiUrl,
    );
});

$container->set(QuoteRepository::class, function (Container $c): QuoteRepository {
    $cfg = $c->get(Config::class);
    return new QuoteRepository(
        $c->get(DatabaseHelper::class),
        $cfg->quoteTableName,
        $cfg->quoteDateFormat
    );
});

$container->set(FetchQuoteUseCase::class, function (Container $c): FetchQuoteUseCase {
    return new FetchQuoteUseCase(
        $c->get(LoggerInterface::class),
        $c->get(QuoteApiService::class),
        $c->get(QuoteRepository::class),
    );
});

$container->set(WordApiService::class, function (Container $c): WordApiService {
    $cfg = $c->get(Config::class);
    return new WordApiService(
        $c->get(LoggerInterface::class),
        $c->get(HttpClientInterface::class),
        $cfg->wordApiUrl,
    );
});

$container->set(WordRepository::class, function (Container $c): WordRepository {
    $cfg = $c->get(Config::class);
    return new WordRepository(
        $c->get(DatabaseHelper::class),
        $cfg->wordTableName,
        $cfg->wordDateFormat,
    );
});

$container->set(FetchWordUseCase::class, function (Container $c): FetchWordUseCase {
    return new FetchWordUseCase(
        $c->get(LoggerInterface::class),
        $c->get(WordApiService::class),
        $c->get(WordRepository::class),
    );
});

// ErrorController
$container->set(ErrorController::class, function (Container $c): ErrorController {
    return new ErrorController(
        $c->get(RequestContext::class),
        $c->get(TwigRenderer::class),
        $c->get(FlashMessengerService::class),
    );
});

//DisplayController
$container->set(DisplayController::class, function (Container $c) {
        $cfg = $c->get(Config::class);
        return new DisplayController(
            $c->get(RequestContext::class),
            $c->get(TwigRenderer::class),
            $c->get(FlashMessengerService::class),
            $c->get(LoggerInterface::class),
            $c->get(WeatherService::class),
            $c->get(IsModuleVisibleUseCase::class),
            $c->get(TramService::class),
            $c->get(CalendarService::class),
            $c->get(GetValidAnnouncementsUseCase::class),
            $c->get(GetUserByIdUseCase::class),
            $c->get(GetCurrentCountdownUseCase::class),
            $c->get(FetchActiveQuoteUseCase::class),
            $c->get(FetchActiveWordUseCase::class),
            $cfg->stopID,
        );
});

// PanelController
    $container->set(PanelController::class, function (Container $c) {
        return new PanelController(
            $c->get(RequestContext::class),
            $c->get(TwigRenderer::class),
            $c->get(FlashMessengerService::class),
            $c->get(LoggerInterface::class),
            $c->get(GetAllModulesUseCase::class),
            $c->get(GetAllUsersUseCase::class),
            $c->get(GetAllCountdownsUseCase::class),
            $c->get(GetAllAnnouncementsUseCase::class),
            $c->get(Translator::class),
        );
    });

$container->set(CalendarService::class, function (Container $c): CalendarService {
    $cfg = $c->get(Config::class);
    return new CalendarService(
        $c->get(LoggerInterface::class),
        $cfg->googleCalendarApiKey,
        $cfg->googleCalendarId,
    );
});

return $container;
