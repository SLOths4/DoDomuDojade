<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$allowedExtensions = ['css', 'js', 'png', 'jpg', 'ico'];
$ext = pathinfo($uri, PATHINFO_EXTENSION);
if ($uri !== '/' && file_exists(__DIR__ . $uri) && in_array($ext, $allowedExtensions)) {
    return false;
}

require_once __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);
$dotenv = Dotenv\Dotenv::createImmutable($root);
$dotenv->safeLoad();

$container = require __DIR__ . '/../src/bootstrap/container.php';

try {
    registerErrorHandling($container);
} catch (Throwable $e) {
    ini_set('display_errors', getenv('APP_ENV') === 'dev' ? '1' : '0');
    ini_set('display_startup_errors', getenv('APP_ENV') === 'dev' ? '1' : '0');
    error_reporting(E_ALL);

    set_exception_handler(static function (Throwable $ex) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo getenv('APP_ENV') === 'dev' ? ('Unhandled exception: ' . $ex->getMessage()) : 'Internal Server Error';
    });

    register_shutdown_function(static function () {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo getenv('APP_ENV') === 'dev' ? 'Fatal error (shutdown)' : 'Internal Server Error';
        }
    });
}

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
        $container->get(ErrorController::class)->notFound();
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $container->get(ErrorController::class)->methodNotAllowed();
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_array($handler)) {
            $controller = $container->get($handler[0]);
            call_user_func([$controller, $handler[1]], $vars);
        } else {
            call_user_func($handler, $vars);
        }
        break;
    default:
        $container->get(ErrorController::class)->internalServerError();
        break;
}