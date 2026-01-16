<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__. '/../../../'));
$dotenv->load();

use App\Application\Announcement\CreateAnnouncementUseCase;
use App\Application\Announcement\DeleteAnnouncementUseCase;
use App\Application\Announcement\DeleteRejectedSinceAnnouncementUseCase;
use App\Application\Announcement\EditAnnouncementUseCase;
use App\Application\Announcement\GetAllAnnouncementsUseCase;
use App\Application\Announcement\GetValidAnnouncementsUseCase;
use App\Application\Countdown\CreateCountdownUseCase;
use App\Application\Countdown\DeleteCountdownUseCase;
use App\Application\Countdown\GetAllCountdownsUseCase;
use App\Application\Countdown\GetCountdownByIdUseCase;
use App\Application\Countdown\GetCurrentCountdownUseCase;
use App\Application\Countdown\UpdateCountdownUseCase;
use App\Application\Display\GetDeparturesUseCase;
use App\Application\Display\GetDisplayAnnouncementsUseCase;
use App\Application\Display\GetDisplayCountdownUseCase;
use App\Application\Display\GetDisplayEventsUseCase;
use App\Application\Display\GetDisplayQuoteUseCase;
use App\Application\Display\GetDisplayWeatherUseCase;
use App\Application\Display\GetDisplayWordUseCase;
use App\Application\Module\GetAllModulesUseCase;
use App\Application\Module\GetModuleByIdUseCase;
use App\Application\Module\IsModuleVisibleUseCase;
use App\Application\Module\ToggleModuleUseCase;
use App\Application\Module\UpdateModuleUseCase;
use App\Application\Quote\FetchActiveQuoteUseCase;
use App\Application\Quote\FetchQuoteUseCase;
use App\Application\User\ChangePasswordUseCase;
use App\Application\User\CreateUserUseCase;
use App\Application\User\DeleteUserUseCase;
use App\Application\User\GetAllUsersUseCase;
use App\Application\User\GetUserByIdUseCase;
use App\Application\User\GetUserByUsernameUseCase;
use App\Application\User\UpdateUserUseCase;
use App\Application\Word\FetchActiveWordUseCase;
use App\Application\Word\FetchWordUseCase;
use App\config\Config;
use App\Console\CommandRegistry;
use App\Console\Commands\AnnouncementRejectedDeleteCommand;
use App\Console\Commands\QuoteFetchCommand;
use App\Console\Commands\WordFetchCommand;
use App\Console\Kernel;
use App\Domain\Event\EventPublisher;
use App\Http\Context\LocaleContext;
use App\Http\Context\RequestContext;
use App\Http\Controller\DisplayController;
use App\Http\Controller\ErrorController;
use App\Http\Controller\PanelController;
use App\Http\Service\RedirectService;
use App\Infrastructure\Container;
use App\Infrastructure\Event\RedisEventPublisher;
use App\Infrastructure\Factory\LoggerFactory;
use App\Infrastructure\Factory\PDOFactory;
use App\Infrastructure\Factory\TwigFactory;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Helper\CountdownValidationHelper;
use App\Infrastructure\Helper\DatabaseHelper;
use App\Infrastructure\Helper\ModuleValidationHelper;
use App\Infrastructure\Persistence\CountdownRepository;
use App\Infrastructure\Persistence\ModuleRepository;
use App\Infrastructure\Persistence\PDOAnnouncementRepository;
use App\Infrastructure\Persistence\PDOEventStore;
use App\Infrastructure\Persistence\QuoteRepository;
use App\Infrastructure\Persistence\UserRepository;
use App\Infrastructure\Persistence\WordRepository;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Service\CalendarService;
use App\Infrastructure\Service\CsrfTokenService;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\Service\FlashMessengerService;
use App\Infrastructure\Service\QuoteApiService;
use App\Infrastructure\Service\TramService;
use App\Infrastructure\Service\WeatherService;
use App\Infrastructure\Service\WordApiService;
use App\Infrastructure\Translation\LanguageTranslator;
use App\Infrastructure\Translation\Translator;
use App\Infrastructure\View\TwigRenderer;
use App\Infrastructure\View\ViewRendererInterface;
use Predis\Client;
use Psr\Log\LoggerInterface;
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
$container->set(PDO::class, function(Container $c): PDO {
    $cfg = $c->get(Config::class);
    return $c->get(PDOFactory::class)->create(
        $cfg->dbDsn(),
        $cfg->dbUsername(),
        $cfg->dbPassword(),
    );
});

// Redis
$container->set(Client::class, function(Container $c): Client {
    $cfg = $c->get(Config::class);
    return new Client(
        [
            'scheme' => 'tcp',
            'host' => $cfg->redisHost,
            'port' => $cfg->redisPort
        ]
    );
});

$container->set(EventPublisher::class, function(Container $c): EventPublisher {
    return new RedisEventPublisher(
        $c->get(Client::class),
        $c->get(PDOEventStore::class),
        $c->get(LoggerInterface::class),
    );
});


// DatabaseHelper
$container->set(DatabaseHelper::class, fn() => new DatabaseHelper($container->get(PDO::class), $container->get(LoggerInterface::class)));

// HTTP client
$container->set(HttpClientInterface::class, fn() => HttpClient::create());

// RedirectService
$container->set(RedirectService::class, fn(Container $c) => new RedirectService($c->get(RequestContext::class)));

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

$container->set(Environment::class, function(Container $c): Environment {
    $cfg = $c->get(Config::class);
    return $c->get(TwigFactory::class)->create(
            $cfg->viewPath,
            $cfg->twigCachePath,
            $cfg->twigDebug,
        );
});

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
$container->set(PDOAnnouncementRepository::class, function (Container $c): PDOAnnouncementRepository {
    $cfg = $c->get(Config::class);
    return new PDOAnnouncementRepository(
        $c->get(DatabaseHelper::class),
        $cfg->announcementTableName,
        $cfg->announcementDateFormat,
    );
});

// Announcement Use Cases
$container->set(CreateAnnouncementUseCase::class, fn(Container $c) => new CreateAnnouncementUseCase($c->get(PDOAnnouncementRepository::class), $c->get(LoggerInterface::class), $c->get(AnnouncementValidationHelper::class), $c->get(EventPublisher::class) ));
$container->set(DeleteAnnouncementUseCase::class, fn(Container $c) => new DeleteAnnouncementUseCase($c->get(PDOAnnouncementRepository::class), $c->get(LoggerInterface::class), $c->get(AnnouncementValidationHelper::class), $c->get(EventPublisher::class) ));
$container->set(DeleteRejectedSinceAnnouncementUseCase::class, fn(Container $c) => new DeleteRejectedSinceAnnouncementUseCase($c->get(PDOAnnouncementRepository::class), $c->get(LoggerInterface::class)));
$container->set(EditAnnouncementUseCase::class, fn(Container $c) => new EditAnnouncementUseCase($c->get(PDOAnnouncementRepository::class), $c->get(LoggerInterface::class), $c->get(AnnouncementValidationHelper::class), $c->get(EventPublisher::class) ));
$container->set(GetAllAnnouncementsUseCase::class, fn(Container $c) => new GetAllAnnouncementsUseCase($c->get(PDOAnnouncementRepository::class), $c->get(LoggerInterface::class)));
$container->set(GetValidAnnouncementsUseCase::class, fn(Container $c) => new GetValidAnnouncementsUseCase($c->get(PDOAnnouncementRepository::class), $c->get(LoggerInterface::class)));

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
$container->set(ToggleModuleUseCase::class, fn(Container $c) => new ToggleModuleUseCase($c->get(ModuleRepository::class), $c->get(LoggerInterface::class), $c->get(ModuleValidationHelper::class), $c->get(EventPublisher::class) ));
$container->set(UpdateModuleUseCase::class, function(Container $c) {
    return new UpdateModuleUseCase(
        $c->get(ModuleRepository::class),
        $c->get(LoggerInterface::class),
        $c->get(ModuleValidationHelper::class),
        $c->get(EventPublisher::class),
    );
});

$container->set(ApproveRejectAnnouncementUseCase::class, fn(Container $c) => new ApproveRejectAnnouncementUseCase($c->get(PDOAnnouncementRepository::class), $c->get(LoggerInterface::class), $c->get(AnnouncementValidationHelper::class), $c->get(EventPublisher::class) ));

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
        $c->get(EventPublisher::class),
    );
});
$container->set(DeleteCountdownUseCase::class, fn(Container $c) => new DeleteCountdownUseCase($c->get(CountdownRepository::class), $c->get(LoggerInterface::class), $c->get(CountdownValidationHelper::class), $c->get(EventPublisher::class) ));
$container->set(GetAllCountdownsUseCase::class, fn(Container $c) => new GetAllCountdownsUseCase($c->get(CountdownRepository::class), $c->get(LoggerInterface::class)));
$container->set(GetCountdownByIdUseCase::class, fn(Container $c) => new GetCountdownByIdUseCase($c->get(CountdownRepository::class), $c->get(LoggerInterface::class), $c->get(CountdownValidationHelper::class) ));
$container->set(GetCurrentCountdownUseCase::class, fn(Container $c) => new GetCurrentCountdownUseCase($c->get(CountdownRepository::class), $c->get(LoggerInterface::class)));
$container->set(UpdateCountdownUseCase::class, function(Container $c) {
    return new UpdateCountdownUseCase(
        $c->get(CountdownRepository::class),
        $c->get(LoggerInterface::class),
        $c->get(CountdownValidationHelper::class),
        $c->get(EventPublisher::class),
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

// Display Use Cases
$container->set(GetDeparturesUseCase::class, fn(Container $c) => new GetDeparturesUseCase($c->get(TramService::class), $c->get(LoggerInterface::class)));
$container->set(GetDisplayAnnouncementsUseCase::class, fn(Container $c) => new GetDisplayAnnouncementsUseCase($c->get(GetValidAnnouncementsUseCase::class), $c->get(GetUserByIdUseCase::class), $c->get(LoggerInterface::class)));
$container->set(GetDisplayCountdownUseCase::class, fn(Container $c) => new GetDisplayCountdownUseCase($c->get(GetCurrentCountdownUseCase::class)));
$container->set(GetDisplayWeatherUseCase::class, fn(Container $c) => new GetDisplayWeatherUseCase($c->get(WeatherService::class), $c->get(LoggerInterface::class)));
$container->set(GetDisplayEventsUseCase::class, fn(Container $c) => new GetDisplayEventsUseCase($c->get(CalendarService::class), $c->get(LoggerInterface::class)));
$container->set(GetDisplayQuoteUseCase::class, fn(Container $c) => new GetDisplayQuoteUseCase($c->get(FetchActiveQuoteUseCase::class), $c->get(LoggerInterface::class)));
$container->set(GetDisplayWordUseCase::class, fn(Container $c) => new GetDisplayWordUseCase($c->get(FetchActiveWordUseCase::class), $c->get(LoggerInterface::class)));

//DisplayController
$container->set(DisplayController::class, function (Container $c) {
        $cfg = $c->get(Config::class);
        return new DisplayController(
            $c->get(RequestContext::class),
            $c->get(TwigRenderer::class),
            $c->get(FlashMessengerService::class),
            $c->get(LoggerInterface::class),
            $c->get(IsModuleVisibleUseCase::class),
            $c->get(GetDeparturesUseCase::class),
            $c->get(GetDisplayAnnouncementsUseCase::class),
            $c->get(GetDisplayCountdownUseCase::class),
            $c->get(GetDisplayWeatherUseCase::class),
            $c->get(GetDisplayEventsUseCase::class),
            $c->get(GetDisplayQuoteUseCase::class),
            $c->get(GetDisplayWordUseCase::class),
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
