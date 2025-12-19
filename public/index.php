<?php
declare(strict_types=1);

use App\Infrastructure\Helper\StaticFileHandlingHelper;
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

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('vendor/autoload.php not found. Run: composer install');
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap/ErrorHandling.php';

$container = require_once __DIR__ . '/../src/bootstrap/bootstrap.php';
registerErrorHandling($container);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (!is_string($requestUri)) {
    $requestUri = '/';
}

$uri = parse_url($requestUri, PHP_URL_PATH) ?? '/';

$HttpMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$validMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
if (!in_array($HttpMethod, $validMethods, true)) {
    http_response_code(400);
    die('Invalid HTTP method');
}

$staticHandler = new StaticFileHandlingHelper(__DIR__);
if ($staticHandler->serve($requestUri)) {
    return true;
}

try {
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
} catch (Throwable $e) {
    error_log('Router initialization failed: ' . $e->getMessage());
    http_response_code(500);
    die('Internal server error');
}

try {
    $routeInfo = $dispatcher->dispatch($HttpMethod, $uri);
} catch (Throwable $e) {
    error_log('Dispatch failed: ' . $e->getMessage());
    $container->get(ErrorController::class)->internalServerError();
    exit;
}

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

        try {
            if (is_array($handler)) {
                if (count($handler) !== 2) {
                    throw new RuntimeException(
                        'Handler array must contain exactly 2 elements [class, method]'
                    );
                }

                [$controllerClass, $methodName] = $handler;

                if (!is_string($controllerClass) || !is_string($methodName)) {
                    throw new RuntimeException(
                        'Handler class and method names must be strings'
                    );
                }

                try {
                    $controller = $container->get($controllerClass);
                } catch (Throwable $e) {
                    throw new RuntimeException(
                        "Cannot instantiate controller '$controllerClass': " . $e->getMessage(),
                        0,
                        $e
                    );
                }

                if (!method_exists($controller, $methodName)) {
                    throw new RuntimeException(
                        sprintf(
                            'Method %s::%s does not exist',
                            get_class($controller),
                            $methodName
                        )
                    );
                }

                $controller->$methodName($vars);

            } elseif (is_callable($handler)) {
                $handler($vars);

            } else {
                throw new RuntimeException(
                    'Handler must be array [class, method] or callable'
                );
            }
        } catch (Throwable $e) {
            error_log(sprintf(
                'Controller execution failed for %s %s: %s',
                $HttpMethod,
                $uri,
                $e->getMessage()
            ));
            $container->get(ErrorController::class)->internalServerError();
            exit;
        }
        break;

    default:
        error_log('Unknown dispatcher status: ' . $routeInfo[0]);
        $container->get(ErrorController::class)->internalServerError();
        break;
}
