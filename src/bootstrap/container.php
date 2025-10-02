<?php
declare(strict_types=1);

use src\controllers\DisplayController;
use src\controllers\ErrorController;
use src\infrastructure\container\Container;
use src\infrastructure\factories\LoggerFactory;
use src\config\config;
use src\infrastructure\factories\PDOFactory;
use src\models\AnnouncementsModel;
use src\models\CalendarModel;
use src\models\CountdownModel;
use src\models\ModuleModel;
use src\models\TramModel;
use src\models\UserModel;
use src\models\WeatherModel;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;

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


//DisplayController
$container->set(DisplayController::class, function (Container $c) {
    $cfg = $c->get(config::class);

    return new DisplayController(
        $c->get(LoggerInterface::class),
        $c->get(WeatherModel::class),
        $c->get(ModuleModel::class),
        $c->get(TramModel::class),
        $c->get(AnnouncementsModel::class),
        $c->get(UserModel::class),
        $c->get(CountdownModel::class),
        $c->get(CalendarModel::class),
        $cfg->stopsIDs(),
    );
});

// WeatherModel
$container->set(WeatherModel::class, function (Container $c): WeatherModel {
    $cfg = $c->get(config::class);
    return new WeatherModel(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
        $c->get(HttpClientInterface::class),
        $cfg->imgwWeatherUrl(),
        $cfg->airlyEndpoint(),
        $cfg->airlyApiKey(),
        $cfg->airlyLocationId(),
    );
});

// CountdownModel
$container->set(CountdownModel::class, function (Container $c): CountdownModel {
    $cfg = $c->get(config::class);
    return new CountdownModel(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
        $cfg->countdownsTableName(),
        $cfg->countdownsTableColumns(),
    );
});

// ModuleModel
$container->set(ModuleModel::class, function (Container $c): ModuleModel {
    $cfg = $c->get(config::class);
    return new ModuleModel(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
        $cfg->modulesTableName(),
        $cfg->modulesTableColumns(),
    );
});

// AnnouncementsModel
$container->set(AnnouncementsModel::class, function (Container $c): AnnouncementsModel {
    $cfg = $c->get(config::class);
    return new AnnouncementsModel(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
        $cfg->announcementsTableName(),
        $cfg->announcementsDateFormat(),
        $cfg->announcementsMaxTitleLength(),
        $cfg->announcementsMaxTextLength(),
        $cfg->announcementsTableColumns(),
    );
});

// UserModel
$container->set(UserModel::class, function (Container $c): UserModel {
    $cfg = $c->get(config::class);
    return new UserModel(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
        $cfg->usersTableName(),
    );
});

// TramModel
$container->set(TramModel::class, function (Container $c): TramModel {
    $cfg = $c->get(config::class);
    return new TramModel(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
        $c->get(HttpClientInterface::class),
        $cfg->tramUrl(),
    );
});

// CalendarModel
$container->set(CalendarModel::class, function (Container $c): CalendarModel {
    $cfg = $c->get(config::class);
    return new CalendarModel(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class),
        $cfg->icalURL(),
    );
});

return $container;