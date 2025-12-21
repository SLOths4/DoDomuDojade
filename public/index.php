<?php
declare(strict_types=1);

use App\Infrastructure\Exception\UserException;
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
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\MiddlewarePipeline;
use function FastRoute\simpleDispatcher;

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('vendor/autoload.php not found. Run: composer install');
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap/ErrorHandling.php';

$container = require_once __DIR__ . '/../src/bootstrap/bootstrap.php';
/** @noinspection PhpUnhandledExceptionInspection */
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
        $r->addRoute('GET', '/panel', [PanelController::class, 'index', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('GET', '/login', [PanelController::class, 'login']);
        $r->addRoute('GET', '/logout', [PanelController::class, 'logout']);

        // 2. Trasy panelu administracyjnego
        // 2.1. Akcje użytkownika
        $r->addRoute('POST', '/panel/authenticate', [PanelController::class, 'authenticate', 'middleware' => [CsrfMiddleware::class]]);
        $r->addRoute('POST', '/panel/add_user', [UserController::class, 'addUser', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]]);
        $r->addRoute('POST', '/panel/delete_user', [UserController::class, 'deleteUser', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]]);

        // 2.2. Akcje ogłoszeń
        $r->addRoute('POST', '/panel/add_announcement', [AnnouncementController::class, 'addAnnouncement', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]]);
        $r->addRoute('POST', '/panel/delete_announcement', [AnnouncementController::class, 'deleteAnnouncement', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]]);
        $r->addRoute('POST', '/panel/edit_announcement', [AnnouncementController::class, 'editAnnouncement', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]]);

        // 2.3. Akcje powiązane z modułami
        $r->addRoute('POST', '/panel/edit_module', [ModuleController::class, 'editModule', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]]);
        $r->addRoute('POST', '/panel/toggle_module', [ModuleController::class, 'toggleModule', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]]);

        // 2.4. Akcje licznika
        $r->addRoute('POST', '/panel/add_countdown', [CountdownController::class, 'addCountdown', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]]);
        $r->addRoute('POST', '/panel/delete_countdown', [CountdownController::class, 'deleteCountdown', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]]);
        $r->addRoute('POST', '/panel/edit_countdown', [CountdownController::class, 'editCountdown', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]]);

        // 2.5. Wyświetlenie stron panelu
        $r->addRoute('GET', '/panel/users', [PanelController::class, 'users', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('GET', '/panel/countdowns', [PanelController::class, 'countdowns', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('GET', '/panel/announcements', [PanelController::class, 'announcements', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('GET', '/panel/modules', [PanelController::class, 'modules', 'middleware' => [AuthMiddleware::class]]);

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
        $handlerData = $routeInfo[1];
        $vars = $routeInfo[2];

        try {
            if (is_array($handlerData)) {
                $controllerClass = $handlerData[0];
                $methodName = $handlerData[1];
                $middlewares = $handlerData['middleware'] ?? [];

                $controller = $container->get($controllerClass);

                $pipeline = new MiddlewarePipeline();
                foreach ($middlewares as $mwClass) {
                    $pipeline->add($container->get($mwClass));
                }

                $pipeline->run(fn() => $controller->$methodName($vars));

            } elseif (is_callable($handlerData)) {
                $handlerData($vars);
            } else {
                throw new RuntimeException(
                    'Handler must be array [class, method] or callable'
                );
            }
        } catch (UserException $e) {
            http_response_code(401);
            header('Location: /login');
            exit;
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
