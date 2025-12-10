<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__. '/../../../'));
$dotenv->load();

use App\Application\UseCase\Quote\FetchActiveQuoteUseCase;
use App\Application\UseCase\Quote\FetchQuoteUseCase;
use App\Infrastructure\Repository\QuoteRepository;
use App\Infrastructure\Service\QuoteApiService;
use App\Application\UseCase\Word\FetchActiveWordUseCase;
use App\Application\UseCase\Word\FetchWordUseCase;
use App\Infrastructure\Repository\WordRepository;
use App\Infrastructure\Service\WordApiService;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\AnnouncementService;
use App\Application\UseCase\CountdownService;
use App\Application\UseCase\ModuleService;
use App\Infrastructure\Service\TramService;
use App\Application\UseCase\UserService;
use App\Infrastructure\Service\WeatherService;
use App\Config\Config;
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
use App\Infrastructure\Security\CsrfService;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

$container = new Container();

// Logger
$container->set(LoggerInterface::class, fn() => LoggerFactory::create());

// PDO
$container->set(PDO::class, fn() => PDOFactory::create());

// DatabaseHelper
$container->set(DatabaseHelper::class, fn() => new DatabaseHelper($container->get(PDO::class), $container->get(LoggerInterface::class)));

// HTTP client
$container->set(HttpClientInterface::class, fn() => HttpClient::create());

// Config
$container->set(Config::class, fn() => Config::fromEnv());

// AuthenticationService
$container->set(AuthenticationService::class, fn() => new AuthenticationService);

// CsrfService
$container->set(CsrfService::class, fn() => new CsrfService);

// ErrorController
$container->set(ErrorController::class, fn() => new ErrorController($container->get(AuthenticationService::class), $container->get(CsrfService::class), $container->get(LoggerInterface::class)));

//DisplayController
$container->set(DisplayController::class, function (Container $c) {
    $cfg = $c->get(Config::class);
    return new DisplayController(
        $c->get(AuthenticationService::class),
        $c->get(CsrfService::class),
        $c->get(LoggerInterface::class),
        $c->get(WeatherService::class),
        $c->get(ModuleService::class),
        $c->get(TramService::class),
        $c->get(AnnouncementService::class),
        $c->get(UserService::class),
        $c->get(CountdownService::class),
        $c->get(FetchActiveQuoteUseCase::class),
        $c->get(FetchActiveWordUseCase::class),
        $cfg->stopsIDs,
    );
});

// PanelController
$container->set(PanelController::class, function (Container $c) {
    return new PanelController(
        $c->get(AuthenticationService::class),
        $c->get(CsrfService::class),
        $c->get(LoggerInterface::class),
        $c->get(ErrorController::class),
        $c->get(ModuleService::class),
        $c->get(UserService::class),
        $c->get(CountdownService::class),
        $c->get(AnnouncementService::class),
    );
});

// ANNOUNCEMENTS
// AnnouncementsRepository
$container->set(AnnouncementRepository::class, function (Container $c): AnnouncementRepository {
    $cfg = $c->get(Config::class);
    return new AnnouncementRepository(
        $c->get(DatabaseHelper::class),
        $cfg->announcementsTableName,
        $cfg->announcementsDateFormat,
    );
});
// AnnouncementsModel
$container->set(AnnouncementService::class, function (Container $c): AnnouncementService {
    $cfg = $c->get(Config::class);
    return new AnnouncementService(
        $c->get(AnnouncementRepository::class),
        $cfg->announcementsMaxTitleLength,
        $cfg->announcementsMaxTextLength,
        $c->get(LoggerInterface::class),
    );
});

// USERS
// UserModel
$container->set(UserRepository::class, function (Container $c): UserRepository {
    $cfg = $c->get(Config::class);
    return new UserRepository(
        $c->get(DatabaseHelper::class),
        $cfg->usersTableName,
        $cfg->usersDateFormat,
    );
});
// UserService
$container->set(UserService::class, function (Container $c): UserService {
    $cfg = $c->get(Config::class);
    return new UserService(
        $c->get(UserRepository::class),
        $cfg->maxUsernameLength,
        $cfg->minPasswordLength,
        $c->get(LoggerInterface::class),
    );
});

// MODULES
// ModuleRepository
$container->set(ModuleRepository::class, function (Container $c): ModuleRepository {
    $cfg = $c->get(Config::class);
    return new ModuleRepository(
        $c->get(DatabaseHelper::class),
        $cfg->modulesTableName,
        $cfg->modulesDateFormat,
    );
});
// ModuleService
$container->set(ModuleService::class, function (Container $c): ModuleService {
    $cfg = $c->get(Config::class);
    return new ModuleService(
        $c->get(ModuleRepository::class),
        $cfg->modulesDateFormat,
        $c->get(LoggerInterface::class),
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
        $cfg->countdownsTableName,
        $cfg->countdownsDateFormat,
    );
});
// CountdownService
$container->set(CountdownService::class, function (Container $c): CountdownService {
    $cfg = $c->get(Config::class);
    return new CountdownService(
        $c->get(CountdownRepository::class),
        $cfg->countdownsMaxTitleLength,
        $cfg->countdownsDateFormat,
        $c->get(LoggerInterface::class),
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

return $container;