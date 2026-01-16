<?php
declare(strict_types=1);

use App\Http\Controller\AnnouncementController;
use App\Http\Controller\CountdownController;
use App\Http\Controller\DisplayController;
use App\Http\Controller\ErrorController;
use App\Http\Controller\HomeController;
use App\Http\Controller\LoginController;
use App\Http\Controller\ModuleController;
use App\Http\Controller\PanelController;
use App\Http\Controller\UserController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\ExceptionMiddleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\MiddlewarePipeline;
use App\Http\Middleware\RequestContextMiddleware;
use App\Infrastructure\Helper\StaticFileHandlingHelper;
use FastRoute\RouteCollector;
use GuzzleHttp\Psr7\ServerRequest;
use function FastRoute\simpleDispatcher;

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('vendor/autoload.php not found. Run: composer install');
}

require_once __DIR__ . '/../vendor/autoload.php';

$container = require_once __DIR__ . '/../src/bootstrap/bootstrap.php';

$request = ServerRequest::fromGlobals();

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (!is_string($requestUri)) {
    $requestUri = '/';
}

$uri = $request->getUri()->getPath();
$HttpMethod = $request->getMethod();

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
        $r->addRoute('GET', '/login', [LoginController::class, 'show']);
        $r->addRoute('GET', '/logout', [LoginController::class, 'logout']);

        // 2. Trasy panelu administracyjnego
        // 2.1. Akcje użytkownika
        $r->addRoute('POST', '/panel/authenticate', [LoginController::class, 'authenticate']);
        $r->addRoute('POST', '/panel/add_user', [UserController::class, 'addUser', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('POST', '/panel/delete_user', [UserController::class, 'deleteUser', 'middleware' => [AuthMiddleware::class]]);

        // 2.2. Akcje ogłoszeń
        $r->addRoute('POST', '/panel/add_announcement', [AnnouncementController::class, 'addAnnouncement', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('POST', '/panel/delete_announcement', [AnnouncementController::class, 'deleteAnnouncement', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('POST', '/panel/edit_announcement', [AnnouncementController::class, 'editAnnouncement', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('POST', '/panel/approve_announcement', [AnnouncementController::class, 'approveAnnouncement', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('POST', '/panel/reject_announcement', [AnnouncementController::class, 'rejectAnnouncement', 'middleware' => [AuthMiddleware::class,]]);

        // 2.3. Akcje powiązane z modułami
        $r->addRoute('POST', '/panel/edit_module', [ModuleController::class, 'editModule', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('POST', '/panel/toggle_module', [ModuleController::class, 'toggleModule', 'middleware' => [AuthMiddleware::class]]);

        // 2.4. Akcje licznika
        $r->addRoute('POST', '/panel/add_countdown', [CountdownController::class, 'addCountdown', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('POST', '/panel/delete_countdown', [CountdownController::class, 'deleteCountdown', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('POST', '/panel/edit_countdown', [CountdownController::class, 'editCountdown', 'middleware' => [AuthMiddleware::class]]);

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

        // 4. Trasy ogólnodostępne (public)
        $r->addRoute('GET', '/propose', [HomeController::class, 'proposeAnnouncement']);
        $r->addRoute('POST', '/public/announcement/propose', [AnnouncementController::class, 'proposeAnnouncement']);

        $r->addRoute('GET', '/stream', [\App\Http\Controller\SSEStreamController::class, 'stream', 'enableMiddleware' => false]);
        $r->addRoute('GET', '/test', [PanelController::class, 'test', 'enableMiddleware' => false]);
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

        if (is_array($handlerData)) {
            $controllerClass = $handlerData[0];
            $methodName = $handlerData[1];
            $middlewares = $handlerData['middleware'] ?? [];
            $enableMiddlewares = $handlerData['enableMiddleware'] ?? false;

            $controller = $container->get($controllerClass);

            if (!$enableMiddlewares) {
                $controller->$methodName($vars);
            } else {

                $pipeline = new MiddlewarePipeline();

                $pipeline->add($container->get(RequestContextMiddleware::class));
                $pipeline->add($container->get(ExceptionMiddleware::class));
                $pipeline->add($container->get(LocaleMiddleware::class));
                $pipeline->add($container->get(CsrfMiddleware::class));

                foreach ($middlewares as $mwClass) {
                    $pipeline->add($container->get($mwClass));
                }

                $pipeline->run($request, fn() => $controller->$methodName($vars));
            }
        } elseif (is_callable($handlerData)) {
            $handlerData($vars);
        } else {
            throw new RuntimeException('Handler must be array [class, method] or callable');
        }
        break;

    default:
        error_log('Unknown dispatcher status: ' . $routeInfo[0]);
        $container->get(ErrorController::class)->internalServerError();
        break;
}