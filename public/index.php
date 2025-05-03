<?php

require_once __DIR__ . '/../vendor/autoload.php';

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use src\controllers\ErrorController;
use src\controllers\PanelController;
use src\controllers\DisplayController;
use src\controllers\HomeController;

$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    // 1. Trasy główne
    $r->addRoute('GET', '/', [HomeController::class, 'index']);
    $r->addRoute('GET', '/display', [DisplayController::class, 'index']);
    $r->addRoute('GET', '/panel', [PanelController::class, 'index']);
    $r->addRoute('GET', '/login', [PanelController::class, 'login']);
    $r->addRoute('GET', '/logout', [PanelController::class, 'logout']);

    // 2. Trasy panelu administracyjnego
    // 2.1. Akcje użytkownika
    $r->addRoute('POST', '/panel/authenticate', [PanelController::class, 'authenticate']);
    $r->addRoute('POST', '/panel/add_user', [PanelController::class, 'addUser']);
    $r->addRoute('POST', '/panel/delete_user', [PanelController::class, 'deleteUser']);

    // 2.2. Akcje ogłoszeń
    $r->addRoute('POST', '/panel/add_announcement', [PanelController::class, 'addAnnouncement']);
    $r->addRoute('POST', '/panel/delete_announcement', [PanelController::class, 'deleteAnnouncement']);
    $r->addRoute('POST', '/panel/edit_announcement', [PanelController::class, 'editAnnouncement']);

    // 2.3. Akcje powiązane z modułami
    $r->addRoute('POST', '/panel/edit_module', [PanelController::class, 'editModule']);
    $r->addRoute('POST', '/panel/toggle_module', [PanelController::class, 'toggleModule']);

    // 2.4. Akcje licznika
    $r->addRoute('POST', '/panel/add_countdown', [PanelController::class, 'addCountdown']);
    $r->addRoute('POST', '/panel/delete_countdown', [PanelController::class, 'deleteCountdown']);
    $r->addRoute('POST', '/panel/edit_countdown', [PanelController::class, 'editCountdown']);

    // 2.5. Wyświetlenie stron panelu
    $r->addRoute('GET', '/panel/users', [PanelController::class, 'users']);
    $r->addRoute('GET', '/panel/countdowns', [PanelController::class, 'countdowns']);
    $r->addRoute('GET', '/panel/announcements', [PanelController::class, 'announcements']);
    $r->addRoute('GET', '/panel/modules', [PanelController::class, 'modules']);

    // 3. Trasy wyświetlacza (display)
    $r->addRoute('POST', '/display/get_version', [DisplayController::class, 'getVersion']);
    $r->addRoute('POST', '/display/get_departures', [DisplayController::class, 'getDepartures']);
    $r->addRoute('POST', '/display/get_announcements', [DisplayController::class, 'getAnnouncements']);
    $r->addRoute('POST', '/display/get_weather', [DisplayController::class, 'getWeather']);
    $r->addRoute('POST', '/display/get_countdown', [DisplayController::class, 'getCountdown']);
    $r->addRoute('POST', '/display/get_events', [DisplayController::class, 'getEvents']);
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$uri = strtok($uri, '?');

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        new ErrorController()->notFound();
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        new ErrorController()->methodNotAllowed();
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_array($handler)) {
            $controller = new $handler[0]();
            call_user_func([$controller, $handler[1]], $vars);
        } else {
            call_user_func($handler, $vars);
        }
        break;
    default:
        new ErrorController()->internalServerError();
        break;
}
