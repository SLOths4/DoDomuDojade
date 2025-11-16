<?php
declare(strict_types=1);

use Psr\Log\LoggerInterface;
use src\config\Config;
use src\controllers\DisplayController;
use src\controllers\ErrorController;
use src\controllers\PanelController;
use src\infrastructure\container\Container;
use src\infrastructure\factories\LoggerFactory;
use src\infrastructure\factories\PDOFactory;
use src\infrastructure\helpers\DatabaseHelper;
use src\repository\AnnouncementRepository;
use src\repository\CountdownRepository;
use src\repository\ModuleRepository;
use src\repository\UserRepository;
use src\service\AnnouncementService;
use src\service\CountdownService;
use src\service\ModuleService;
use src\service\TramService;
use src\service\UserService;
use src\service\WeatherService;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

require_once __DIR__ . '/../../vendor/autoload.php';

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

// ErrorController
$container->set(ErrorController::class, fn() => new ErrorController());

//DisplayController
$container->set(DisplayController::class, function (Container $c) {
    $cfg = $c->get(Config::class);

    return new DisplayController(
        $c->get(LoggerInterface::class),
        $c->get(WeatherService::class),
        $c->get(ModuleService::class),
        $c->get(TramService::class),
        $c->get(AnnouncementService::class),
        $c->get(UserService::class),
        $c->get(CountdownService::class),
        $cfg->stopsIDs,
    );
});

// PanelController
$container->set(PanelController::class, function (Container $c) {

    return new PanelController(
        $c->get(ErrorController::class),
        $c->get(LoggerInterface::class),
        $c->get(ModuleService::class),
        $c->get(AnnouncementService::class),
        $c->get(UserService::class),
        $c->get(CountdownService::class),
    );
});

// ANNOUNCEMENTS
// AnnouncementsRepository
$container->set(AnnouncementRepository::class, function (Container $c): AnnouncementRepository {
    $cfg = $c->get(Config::class);
    return new AnnouncementRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
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
    );
});

// USERS
// UserModel
$container->set(UserRepository::class, function (Container $c): UserRepository {
    $cfg = $c->get(Config::class);
    return new UserRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
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
    );
});

// MODULES
// ModuleRepository
$container->set(ModuleRepository::class, function (Container $c): ModuleRepository {
    $cfg = $c->get(Config::class);
    return new ModuleRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
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
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
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

return $container;