<?php

require_once __DIR__ . '/../vendor/autoload.php';

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use src\controllers\ErrorController;
use src\controllers\PanelController;
use src\controllers\DisplayController;
use src\controllers\HomeController;

$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/', [HomeController::class, 'index']);
    $r->addRoute('GET', '/display', [DisplayController::class, 'index']);
    $r->addRoute('GET', '/panel', [PanelController::class, 'index']);
    $r->addRoute('GET', '/login', [PanelController::class, 'login']);
    $r->addRoute('GET', '/logout', [PanelController::class, 'logout']);
    // metody pomocnicze do panelu
    $r->addRoute('POST', '/panel/authenticate', [PanelController::class, 'authenticate']);
    $r->addRoute('POST', '/panel/add_announcement', [PanelController::class, 'addAnnouncement']);
    $r->addRoute('POST', '/panel/delete_announcement', [PanelController::class, 'deleteAnnouncement']);
    $r->addRoute('POST', '/panel/add_user', [PanelController::class, 'addUser']);
    $r->addRoute('POST', '/panel/delete_user', [PanelController::class, 'deleteUser']);
    $r->addRoute('POST', '/panel/edit_user', [PanelController::class, 'editUser']);
    $r->addRoute('POST', '/panel/toggle_module', [PanelController::class, 'toggleModule']);
    // metody pomocnicze do display'u
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
