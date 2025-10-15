<?php
declare(strict_types=1);

use Psr\Log\LoggerInterface;
use src\config\config;
use src\controllers\DisplayController;
use src\controllers\ErrorController;
use src\controllers\PanelController;
use src\infrastructure\container\Container;
use src\infrastructure\factories\LoggerFactory;
use src\infrastructure\factories\PDOFactory;
use src\infrastructure\utils\PDOSchemaAdapter;
use src\infrastructure\utils\SchemaChecker;
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

// HTTP client
$container->set(HttpClientInterface::class, fn() => HttpClient::create());

// Config
$container->set(config::class, fn() => config::fromEnv());

// ErrorController
$container->set(ErrorController::class, fn() => new ErrorController());

$container->set(PDOSchemaAdapter::class, fn() => new PDOSchemaAdapter($container->get(PDO::class), 'sqlite'));

$container->set(SchemaChecker::class, fn() => new SchemaChecker($container->get(PDOSchemaAdapter::class), 'users'));

//DisplayController
$container->set(DisplayController::class, function (Container $c) {
    $cfg = $c->get(config::class);

    return new DisplayController(
        $c->get(LoggerInterface::class),
        $c->get(WeatherService::class),
        $c->get(ModuleService::class),
        $c->get(TramService::class),
        $c->get(AnnouncementService::class),
        $c->get(UserService::class),
        $c->get(CountdownService::class),
        $cfg->stopsIDs(),
    );
});

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

$container->set(AnnouncementRepository::class, function (Container $c): AnnouncementRepository {
    $cfg = $c->get(config::class);
    return new AnnouncementRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
        $cfg->announcementsTableName(),
        $cfg->announcementsDateFormat(),
    );
});

// AnnouncementsModel
$container->set(AnnouncementService::class, function (Container $c): AnnouncementService {
    $cfg = $c->get(config::class);
    return new AnnouncementService(
        $c->get(AnnouncementRepository::class),
        $cfg->announcementsMaxTitleLength(),
        $cfg->announcementsMaxTextLength(),
        $cfg->announcementsTableColumns(),
    );
});

// UserModel
$container->set(UserRepository::class, function (Container $c): UserRepository {
    $cfg = $c->get(config::class);
    return new UserRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
        $cfg->userTableName(),
        $cfg->userDateFormat(),
    );
});

$container->set(UserService::class, function (Container $c): UserService {
    $cfg = $c->get(config::class);
    return new UserService(
        $c->get(UserRepository::class),
        $c->get(SchemaChecker::class),
        $cfg->maxUsernameLength(),
        $cfg->minPasswordLength(),
    );
});

$container->set(ModuleRepository::class, function (Container $c): ModuleRepository {
    $cfg = $c->get(config::class);
    return new ModuleRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
        $cfg->moduleTableName(),
    );
});

$container->set(ModuleService::class, function (Container $c): ModuleService {
    $cfg = $c->get(config::class);
    return new ModuleService(
        $c->get(ModuleRepository::class),
        $cfg->moduleTableColumns(),
    );
});

$container->set(TramService::class, function (Container $c): TramService {
    $cfg = $c->get(config::class);
    return new TramService(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
        $c->get(HttpClientInterface::class),
        $cfg->ztmUrl(),
    );
});

$container->set(CountdownRepository::class, function (Container $c): CountdownRepository {
    $cfg = $c->get(config::class);
    return new CountdownRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
        $cfg->countdownsTableName(),
    );
});

$container->set(CountdownService::class, function (Container $c): CountdownService {
    $cfg = $c->get(config::class);
    return new CountdownService(
        $c->get(CountdownRepository::class),
        $cfg->countdownsMaxTitleLength(),
        $cfg->countdownsTableColumns(),
    );
});

return $container;