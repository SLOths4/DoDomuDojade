<?php
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('vendor/autoload.php not found. Run: composer install');
}
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$allowedExtensions = ['css', 'js', 'png', 'jpg', 'ico'];
$ext = pathinfo($uri, PATHINFO_EXTENSION);
if ($uri !== '/' && file_exists(__DIR__ . $uri) && in_array($ext, $allowedExtensions)) {
    return false;
}

require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/bootstrap/ErrorHandling.php';

$container = require __DIR__ . '/../src/bootstrap/bootstrap.php';

try {
    registerErrorHandling($container);
} catch (Throwable $e) {
    echo 'Error registering error handling: ' . $e->getMessage();
    ini_set('display_errors', getenv('APP_ENV') === 'dev' ? '1' : '0');
    ini_set('display_startup_errors', getenv('APP_ENV') === 'dev' ? '1' : '0');
    error_reporting(E_ALL);

    set_exception_handler(static function (Throwable $ex) {
        Http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo getenv('APP_ENV') === 'dev' ? ('Unhandled exception: ' . $ex->getMessage()) : 'Internal Server Error';
    });

    register_shutdown_function(static function () {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            Http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo getenv('APP_ENV') === 'dev' ? 'Fatal error (shutdown)' : 'Internal Server Error';
        }
    });
}

use FastRoute\RouteCollector;
use App\Http\Controller\AnnouncementController;
use App\Http\Controller\CountdownController;
use App\Http\Controller\DisplayController;
use App\Http\Controller\ErrorController;
use App\Http\Controller\HomeController;
use App\Http\Controller\ModuleController;
use App\Http\Controller\PanelController;
use App\Http\Controller\UserController;
use function FastRoute\simpleDispatcher;

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
    $r->addRoute('POST', '/panel/add_user', [UserController::class, 'addUser']);
    $r->addRoute('POST', '/panel/delete_user', [UserController::class, 'deleteUser']);

    // 2.2. Akcje ogłoszeń
    $r->addRoute('POST', '/panel/add_announcement', [AnnouncementController::class, 'addAnnouncement']);
    $r->addRoute('POST', '/panel/delete_announcement', [AnnouncementController::class, 'deleteAnnouncement']);
    $r->addRoute('POST', '/panel/edit_announcement', [AnnouncementController::class, 'editAnnouncement']);

    // 2.3. Akcje powiązane z modułami
    $r->addRoute('POST', '/panel/edit_module', [ModuleController::class, 'editModule']);
    $r->addRoute('POST', '/panel/toggle_module', [ModuleController::class, 'toggleModule']);

    // 2.4. Akcje licznika
    $r->addRoute('POST', '/panel/add_countdown', [CountdownController::class, 'addCountdown']);
    $r->addRoute('POST', '/panel/delete_countdown', [CountdownController::class, 'deleteCountdown']);
    $r->addRoute('POST', '/panel/edit_countdown', [CountdownController::class, 'editCountdown']);

    // 2.5. Wyświetlenie stron panelu
    $r->addRoute('GET', '/panel/users', [PanelController::class, 'users']);
    $r->addRoute('GET', '/panel/countdowns', [PanelController::class, 'countdowns']);
    $r->addRoute('GET', '/panel/announcements', [PanelController::class, 'announcements']);
    $r->addRoute('GET', '/panel/modules', [PanelController::class, 'modules']);

    // 3. Trasy wyświetlacza (display)
    $r->addRoute('GET', '/display/get_departures', [DisplayController::class, 'getDepartures']);
    $r->addRoute('GET', '/display/get_announcements', [DisplayController::class, 'getAnnouncements']);
    $r->addRoute('GET', '/display/get_weather', [DisplayController::class, 'getWeather']);
    $r->addRoute('GET', '/display/get_countdown', [DisplayController::class, 'getCountdown']);
    $r->addRoute('GET', '/display/get_events', [DisplayController::class, 'getEvents']);
    $r->addRoute('GET', '/display/get_quote', [DisplayController::class, 'getQuote']);
    $r->addRoute('GET', '/display/get_word', [DisplayController::class, 'getWord']);
});

$HttpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$uri = strtok($uri, '?');

$routeInfo = $dispatcher->dispatch($HttpMethod, $uri);

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