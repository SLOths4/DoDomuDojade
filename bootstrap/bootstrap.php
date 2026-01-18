<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../vendor/autoload.php';

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    error_log(sprintf(
        'PHP Error [%d]: %s in %s:%d',
        $errno,
        $errstr,
        $errfile,
        $errline
    ));
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

register_shutdown_function(function (): void {
    $error = error_get_last();

    if ($error === null) {
        return;
    }

    if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    error_log(sprintf(
        'Fatal Error [%d]: %s in %s:%d',
        $error['type'],
        $error['message'],
        $error['file'],
        $error['line']
    ));

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Internal server error',
        'code' => 'INTERNAL_SERVER_ERROR',
    ]);
});

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

use App\Application\Announcement\UseCase\ApproveRejectAnnouncementUseCase;
use App\Application\Announcement\UseCase\CreateAnnouncementUseCase;
use App\Application\Announcement\UseCase\DeleteAnnouncementUseCase;
use App\Application\Announcement\UseCase\DeleteRejectedSinceAnnouncementUseCase;
use App\Application\Announcement\UseCase\EditAnnouncementUseCase;
use App\Application\Announcement\UseCase\GetAllAnnouncementsUseCase;
use App\Application\Announcement\UseCase\GetValidAnnouncementsUseCase;
use App\Application\Countdown\UseCase\CreateCountdownUseCase;
use App\Application\Countdown\UseCase\DeleteCountdownUseCase;
use App\Application\Countdown\UseCase\GetAllCountdownsUseCase;
use App\Application\Countdown\UseCase\GetCountdownByIdUseCase;
use App\Application\Countdown\UseCase\GetCurrentCountdownUseCase;
use App\Application\Countdown\UseCase\UpdateCountdownUseCase;
use App\Application\Display\GetDeparturesUseCase;
use App\Application\Display\GetDisplayAnnouncementsUseCase;
use App\Application\Display\GetDisplayCountdownUseCase;
use App\Application\Display\GetDisplayEventsUseCase;
use App\Application\Display\GetDisplayQuoteUseCase;
use App\Application\Display\GetDisplayWeatherUseCase;
use App\Application\Display\GetDisplayWordUseCase;
use App\Application\Module\UseCase\GetAllModulesUseCase;
use App\Application\Module\UseCase\GetModuleByIdUseCase;
use App\Application\Module\UseCase\IsModuleVisibleUseCase;
use App\Application\Module\UseCase\ToggleModuleUseCase;
use App\Application\Module\UseCase\UpdateModuleUseCase;
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
use App\Console\CommandRegistry;
use App\Console\Commands\AnnouncementRejectedDeleteCommand;
use App\Console\Commands\QuoteFetchCommand;
use App\Console\Commands\WordFetchCommand;
use App\Console\Kernel;
use App\Domain\Event\EventPublisher;
use App\Infrastructure\Configuration\Config;
use App\Infrastructure\Container;
use App\Infrastructure\Database\DatabaseService;
use App\Infrastructure\Database\PDOFactory;
use App\Infrastructure\Event\RedisEventPublisher;
use App\Infrastructure\ExternalApi\Calendar\CalendarService;
use App\Infrastructure\ExternalApi\Quote\QuoteApiService;
use App\Infrastructure\ExternalApi\Tram\TramService;
use App\Infrastructure\ExternalApi\Weather\WeatherService;
use App\Infrastructure\ExternalApi\Word\WordApiService;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Helper\CountdownValidationHelper;
use App\Infrastructure\Helper\ModuleValidationHelper;
use App\Infrastructure\Logger\LoggerFactory;
use App\Infrastructure\Persistence\PDOAnnouncementRepository;
use App\Infrastructure\Persistence\PDOCountdownRepository;
use App\Infrastructure\Persistence\PDOEventStore;
use App\Infrastructure\Persistence\PDOModuleRepository;
use App\Infrastructure\Persistence\PDOQuoteRepository;
use App\Infrastructure\Persistence\PDOUserRepository;
use App\Infrastructure\Persistence\PDOWordRepository;
use App\Infrastructure\Redis\RedisFactory;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Service\CsrfTokenService;
use App\Infrastructure\Service\FlashMessengerService;
use App\Infrastructure\Translation\LanguageTranslator;
use App\Infrastructure\Twig\TwigFactory;
use App\Infrastructure\Twig\TwigRenderer;
use App\Presentation\Http\Context\LocaleContext;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Controller\DisplayController;
use App\Presentation\Http\Controller\ErrorController;
use App\Presentation\Http\Controller\PanelController;
use App\Presentation\Http\Mapper\AnnouncementViewMapper;
use App\Presentation\Http\Shared\FlashMessengerInterface;
use App\Presentation\Http\Shared\Translator;
use App\Presentation\Http\Shared\ViewRendererInterface;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\ServerRequest;

$container = new Container();

$container->set(ServerRequestInterface::class, fn() => ServerRequest::fromGlobals());


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
    return RedisFactory::createSingleton(
        $cfg->redisHost,
        $cfg->redisPort,
    );
});

$container->set(EventPublisher::class, function(Container $c): EventPublisher {
    return new RedisEventPublisher(
        $c->get(Client::class),
        $c->get(PDOEventStore::class),
        $c->get(LoggerInterface::class),
    );
});

// DatabaseService
$container->set(DatabaseService::class, fn(Container $c) => new DatabaseService(
    $c->get(PDO::class),
    $c->get(LoggerInterface::class)
));

// HTTP client
$container->set(HttpClientInterface::class, fn() => HttpClient::create());

// AuthenticationService
$container->set(AuthenticationService::class, fn(Container $c) => new AuthenticationService(
    $c->get(PDOUserRepository::class)
));

// CsrfTokenService
$container->set(CsrfTokenService::class, fn() => new CsrfTokenService());

$container->set(LocaleContext::class, fn() => new LocaleContext());

$container->set(Translator::class, fn(Container $c) => new LanguageTranslator(
    $c->get(LocaleContext::class),
));

$container->set(CommandRegistry::class, function (Container $c) {
    $registry = new CommandRegistry();
    $registry->register($c->get(QuoteFetchCommand::class));
    $registry->register($c->get(WordFetchCommand::class));
    $registry->register($c->get(AnnouncementRejectedDeleteCommand::class));
    return $registry;
});

$container->set(Kernel::class, fn(Container $c) => new Kernel(
    $c->get(CommandRegistry::class)
));

$container->set(Environment::class, function(Container $c): Environment {
    $cfg = $c->get(Config::class);
    return $c->get(TwigFactory::class)->create(
        dirname(__DIR__),
        $cfg->twigCachePath,
        $cfg->twigDebug,
    );
});

$container->set(ViewRendererInterface::class, fn(Container $c) => new TwigRenderer(
    $c->get(Environment::class),
    $c->get(RequestContext::class),
    $c->get(LocaleContext::class),
    $c->get(FlashMessengerService::class),
    $c->get(Translator::class),
));

$container->set(FlashMessengerInterface::class, fn() => new FlashMessengerService());

// ============ ANNOUNCEMENTS ============

$container->set(PDOAnnouncementRepository::class, function (Container $c): PDOAnnouncementRepository {
    $cfg = $c->get(Config::class);
    return new PDOAnnouncementRepository(
        $c->get(DatabaseService::class),
        $cfg->announcementTableName,
        $cfg->announcementDateFormat,
    );
});

$container->set(CreateAnnouncementUseCase::class, fn(Container $c) => new CreateAnnouncementUseCase(
    $c->get(PDOAnnouncementRepository::class),
    $c->get(LoggerInterface::class),
    $c->get(AnnouncementValidationHelper::class),
    $c->get(EventPublisher::class),
));

$container->set(DeleteAnnouncementUseCase::class, fn(Container $c) => new DeleteAnnouncementUseCase(
    $c->get(PDOAnnouncementRepository::class),
    $c->get(LoggerInterface::class),
    $c->get(AnnouncementValidationHelper::class),
    $c->get(EventPublisher::class),
));

$container->set(DeleteRejectedSinceAnnouncementUseCase::class, fn(Container $c) => new DeleteRejectedSinceAnnouncementUseCase(
    $c->get(PDOAnnouncementRepository::class),
    $c->get(LoggerInterface::class),
));

$container->set(EditAnnouncementUseCase::class, fn(Container $c) => new EditAnnouncementUseCase(
    $c->get(PDOAnnouncementRepository::class),
    $c->get(LoggerInterface::class),
    $c->get(AnnouncementValidationHelper::class),
    $c->get(EventPublisher::class),
));

$container->set(GetAllAnnouncementsUseCase::class, fn(Container $c) => new GetAllAnnouncementsUseCase(
    $c->get(PDOAnnouncementRepository::class),
    $c->get(LoggerInterface::class),
));

$container->set(GetValidAnnouncementsUseCase::class, fn(Container $c) => new GetValidAnnouncementsUseCase(
    $c->get(PDOAnnouncementRepository::class),
    $c->get(LoggerInterface::class),
));

$container->set(ApproveRejectAnnouncementUseCase::class, fn(Container $c) => new ApproveRejectAnnouncementUseCase(
    $c->get(PDOAnnouncementRepository::class),
    $c->get(LoggerInterface::class),
    $c->get(AnnouncementValidationHelper::class),
    $c->get(EventPublisher::class),
));

// ============ USERS ============

$container->set(PDOUserRepository::class, function (Container $c): PDOUserRepository {
    $cfg = $c->get(Config::class);
    return new PDOUserRepository(
        $c->get(DatabaseService::class),
        $cfg->userTableName,
        $cfg->userDateFormat,
    );
});

$container->set(CreateUserUseCase::class, function(Container $c) {
    $cfg = $c->get(Config::class);
    return new CreateUserUseCase(
        $c->get(PDOUserRepository::class),
        $c->get(LoggerInterface::class),
        $cfg->maxUsernameLength,
        $cfg->minPasswordLength,
    );
});

$container->set(DeleteUserUseCase::class, fn(Container $c) => new DeleteUserUseCase(
    $c->get(PDOUserRepository::class),
    $c->get(LoggerInterface::class),
));

$container->set(GetAllUsersUseCase::class, fn(Container $c) => new GetAllUsersUseCase(
    $c->get(PDOUserRepository::class),
    $c->get(LoggerInterface::class),
));

$container->set(GetUserByIdUseCase::class, fn(Container $c) => new GetUserByIdUseCase(
    $c->get(PDOUserRepository::class),
    $c->get(LoggerInterface::class),
));

$container->set(GetUserByUsernameUseCase::class, fn(Container $c) => new GetUserByUsernameUseCase(
    $c->get(PDOUserRepository::class),
    $c->get(LoggerInterface::class),
));

$container->set(UpdateUserUseCase::class, function(Container $c) {
    $cfg = $c->get(Config::class);
    return new UpdateUserUseCase(
        $c->get(PDOUserRepository::class),
        $c->get(LoggerInterface::class),
        $cfg->maxUsernameLength,
        $cfg->minPasswordLength,
    );
});

$container->set(ChangePasswordUseCase::class, function(Container $c) {
    $cfg = $c->get(Config::class);
    return new ChangePasswordUseCase(
        $c->get(PDOUserRepository::class),
        $c->get(LoggerInterface::class),
        $cfg->minPasswordLength,
    );
});

// ============ MODULES ============

$container->set(PDOModuleRepository::class, function (Container $c): PDOModuleRepository {
    $cfg = $c->get(Config::class);
    return new PDOModuleRepository(
        $c->get(DatabaseService::class),
        $cfg->moduleTableName,
        $cfg->moduleDateFormat,
    );
});

$container->set(GetAllModulesUseCase::class, fn(Container $c) => new GetAllModulesUseCase(
    $c->get(PDOModuleRepository::class),
    $c->get(LoggerInterface::class),
));

$container->set(GetModuleByIdUseCase::class, fn(Container $c) => new GetModuleByIdUseCase(
    $c->get(PDOModuleRepository::class),
    $c->get(LoggerInterface::class),
    $c->get(ModuleValidationHelper::class),
));

$container->set(IsModuleVisibleUseCase::class, fn(Container $c) => new IsModuleVisibleUseCase(
    $c->get(PDOModuleRepository::class),
    $c->get(LoggerInterface::class),
));

$container->set(ToggleModuleUseCase::class, fn(Container $c) => new ToggleModuleUseCase(
    $c->get(PDOModuleRepository::class),
    $c->get(LoggerInterface::class),
    $c->get(ModuleValidationHelper::class),
    $c->get(EventPublisher::class),
));

$container->set(UpdateModuleUseCase::class, fn(Container $c) => new UpdateModuleUseCase(
    $c->get(PDOModuleRepository::class),
    $c->get(LoggerInterface::class),
    $c->get(ModuleValidationHelper::class),
    $c->get(EventPublisher::class),
));

// ============ COUNTDOWNS ============

$container->set(PDOCountdownRepository::class, function (Container $c): PDOCountdownRepository {
    $cfg = $c->get(Config::class);
    return new PDOCountdownRepository(
        $c->get(DatabaseService::class),
        $cfg->countdownTableName,
        $cfg->countdownDateFormat,
    );
});

$container->set(CreateCountdownUseCase::class, fn(Container $c) => new CreateCountdownUseCase(
    $c->get(PDOCountdownRepository::class),
    $c->get(LoggerInterface::class),
    $c->get(CountdownValidationHelper::class),
    $c->get(EventPublisher::class),
));

$container->set(DeleteCountdownUseCase::class, fn(Container $c) => new DeleteCountdownUseCase(
    $c->get(PDOCountdownRepository::class),
    $c->get(LoggerInterface::class),
    $c->get(CountdownValidationHelper::class),
    $c->get(EventPublisher::class),
));

$container->set(GetAllCountdownsUseCase::class, fn(Container $c) => new GetAllCountdownsUseCase(
    $c->get(PDOCountdownRepository::class),
    $c->get(LoggerInterface::class),
));

$container->set(GetCountdownByIdUseCase::class, fn(Container $c) => new GetCountdownByIdUseCase(
    $c->get(PDOCountdownRepository::class),
    $c->get(LoggerInterface::class),
    $c->get(CountdownValidationHelper::class),
));

$container->set(GetCurrentCountdownUseCase::class, fn(Container $c) => new GetCurrentCountdownUseCase(
    $c->get(PDOCountdownRepository::class),
    $c->get(LoggerInterface::class),
));

$container->set(UpdateCountdownUseCase::class, fn(Container $c) => new UpdateCountdownUseCase(
    $c->get(PDOCountdownRepository::class),
    $c->get(LoggerInterface::class),
    $c->get(CountdownValidationHelper::class),
    $c->get(EventPublisher::class),
));

// ============ EXTERNAL APIS ============

$container->set(TramService::class, fn(Container $c) => new TramService(
    $c->get(LoggerInterface::class),
    $c->get(HttpClientInterface::class),
    $c->get(Config::class)->tramUrl,
));

$container->set(WeatherService::class, fn(Container $c) => new WeatherService(
    $c->get(LoggerInterface::class),
    $c->get(HttpClientInterface::class),
    $c->get(Config::class)->imgwWeatherUrl,
    $c->get(Config::class)->airlyEndpoint,
    $c->get(Config::class)->airlyApiKey,
    $c->get(Config::class)->airlyLocationId,
));

$container->set(QuoteApiService::class, fn(Container $c) => new QuoteApiService(
    $c->get(LoggerInterface::class),
    $c->get(HttpClientInterface::class),
    $c->get(Config::class)->quoteApiUrl,
));

$container->set(WordApiService::class, fn(Container $c) => new WordApiService(
    $c->get(LoggerInterface::class),
    $c->get(HttpClientInterface::class),
    $c->get(Config::class)->wordApiUrl,
));

$container->set(CalendarService::class, fn(Container $c) => new CalendarService(
    $c->get(LoggerInterface::class),
    $c->get(Config::class)->googleCalendarApiKey,
    $c->get(Config::class)->googleCalendarId,
));

// ============ QUOTE & WORD REPOSITORIES & USE CASES ============

$container->set(PDOQuoteRepository::class, fn(Container $c) => new PDOQuoteRepository(
    $c->get(DatabaseService::class),
    $c->get(Config::class)->quoteTableName,
    $c->get(Config::class)->quoteDateFormat,
));

$container->set(FetchQuoteUseCase::class, fn(Container $c) => new FetchQuoteUseCase(
    $c->get(LoggerInterface::class),
    $c->get(QuoteApiService::class),
    $c->get(PDOQuoteRepository::class),
    $c->get(EventPublisher::class),
));

$container->set(PDOWordRepository::class, fn(Container $c) => new PDOWordRepository(
    $c->get(DatabaseService::class),
    $c->get(Config::class)->wordTableName,
    $c->get(Config::class)->wordDateFormat,
));

$container->set(FetchWordUseCase::class, fn(Container $c) => new FetchWordUseCase(
    $c->get(LoggerInterface::class),
    $c->get(WordApiService::class),
    $c->get(PDOWordRepository::class),
));

// ============ DISPLAY USE CASES ============

$container->set(GetDeparturesUseCase::class, fn(Container $c) => new GetDeparturesUseCase(
    $c->get(TramService::class),
    $c->get(LoggerInterface::class),
));

$container->set(GetDisplayAnnouncementsUseCase::class, fn(Container $c) => new GetDisplayAnnouncementsUseCase(
    $c->get(GetValidAnnouncementsUseCase::class),
    $c->get(GetUserByIdUseCase::class),
    $c->get(LoggerInterface::class),
));

$container->set(GetDisplayCountdownUseCase::class, fn(Container $c) => new GetDisplayCountdownUseCase(
    $c->get(GetCurrentCountdownUseCase::class),
));

$container->set(GetDisplayWeatherUseCase::class, fn(Container $c) => new GetDisplayWeatherUseCase(
    $c->get(WeatherService::class),
    $c->get(LoggerInterface::class),
));

$container->set(GetDisplayEventsUseCase::class, fn(Container $c) => new GetDisplayEventsUseCase(
    $c->get(CalendarService::class),
    $c->get(LoggerInterface::class),
));

$container->set(GetDisplayQuoteUseCase::class, fn(Container $c) => new GetDisplayQuoteUseCase(
    $c->get(FetchActiveQuoteUseCase::class),
    $c->get(LoggerInterface::class),
));

$container->set(GetDisplayWordUseCase::class, fn(Container $c) => new GetDisplayWordUseCase(
    $c->get(FetchActiveWordUseCase::class),
    $c->get(LoggerInterface::class),
));

// ============ CONTROLLERS ============

$container->set(ErrorController::class, fn(Container $c) => new ErrorController(
    $c->get(RequestContext::class),
    $c->get(TwigRenderer::class),
    $c->get(FlashMessengerService::class),
));

$container->set(DisplayController::class, function (Container $c) {
    return new DisplayController(
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
    );
});

$container->set(PanelController::class, fn(Container $c) => new PanelController(
    $c->get(RequestContext::class),
    $c->get(TwigRenderer::class),
    $c->get(FlashMessengerService::class),
    $c->get(LoggerInterface::class),
    $c->get(GetAllModulesUseCase::class),
    $c->get(GetAllUsersUseCase::class),
    $c->get(GetAllCountdownsUseCase::class),
    $c->get(GetAllAnnouncementsUseCase::class),
    $c->get(Translator::class),
    $c->get(AnnouncementViewMapper::class),
));

return $container;
