<?php
declare(strict_types=1);

use App\Infrastructure\Helper\StaticFileHandlingHelper;
use App\Presentation\Http\Controller\AnnouncementController;
use App\Presentation\Http\Controller\CountdownController;
use App\Presentation\Http\Controller\DisplayController;
use App\Presentation\Http\Controller\ErrorController;
use App\Presentation\Http\Controller\HomeController;
use App\Presentation\Http\Controller\LoginController;
use App\Presentation\Http\Controller\ModuleController;
use App\Presentation\Http\Controller\PanelController;
use App\Presentation\Http\Controller\UserController;
use App\Presentation\Http\Middleware\AuthMiddleware;
use App\Presentation\Http\Middleware\CsrfMiddleware;
use App\Presentation\Http\Middleware\ExceptionMiddleware;
use App\Presentation\Http\Middleware\LocaleMiddleware;
use App\Presentation\Http\Middleware\MiddlewarePipeline;
use App\Presentation\Http\Middleware\RequestContextMiddleware;
use FastRoute\RouteCollector;
use GuzzleHttp\Psr7\ServerRequest;
use function FastRoute\simpleDispatcher;

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('vendor/autoload.php not found. Run: composer install');
}

require_once __DIR__ . '/../vendor/autoload.php';

$container = require_once __DIR__ . '/../bootstrap/bootstrap.php';

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
        $r->addRoute('POST', '/logout', [LoginController::class, 'logout']);

        // 2. Trasy panelu administracyjnego
        // 2.1. Akcje użytkownika
        $r->addRoute('POST', '/api/authenticate', [LoginController::class, 'authenticate']);
        $r->addRoute('POST', '/api/user', [UserController::class, 'add', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('DELETE', '/api/user/{id:[a-z0-9_.]+}', [UserController::class, 'delete', 'middleware' => [AuthMiddleware::class]]);

        // 2.2. Akcje ogłoszeń
        $r->addRoute('POST', '/api/announcement', [AnnouncementController::class, 'add', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('GET', '/api/announcement', [AnnouncementController::class, 'getAll', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('GET', '/api/announcement/{id:[a-z0-9_.]+}', [AnnouncementController::class, 'get', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('DELETE', '/api/announcement/{id:[a-z0-9_.]+}', [AnnouncementController::class, 'delete', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('PATCH', '/api/announcement/{id:[a-z0-9_.]+}', [AnnouncementController::class, 'update', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('POST', '/api/announcement/{id:[a-z0-9_.]+}/approve', [AnnouncementController::class, 'approve', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('POST', '/api/announcement/{id:[a-z0-9_.]+}/reject', [AnnouncementController::class, 'reject', 'middleware' => [AuthMiddleware::class,]]);
        $r->addRoute('POST', '/api/announcement/propose', [AnnouncementController::class, 'propose']);


        // 2.3. Akcje powiązane z modułami
        $r->addRoute('PATCH', '/api/module/{id:[a-z0-9_.]+}', [ModuleController::class, 'update', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('POST', '/api/module/{id:[a-z0-9_.]+}/toggle', [ModuleController::class, 'toggle', 'middleware' => [AuthMiddleware::class]]);

        // 2.4. Akcje licznika
        $r->addRoute('POST', '/api/countdown', [CountdownController::class, 'add', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('DELETE', '/api/countdown/{id:[a-z0-9_.]+}', [CountdownController::class, 'delete', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('PATCH', '/api/countdown/{id:[a-z0-9_.]+}', [CountdownController::class, 'update', 'middleware' => [AuthMiddleware::class]]);

        // 2.5. Wyświetlenie stron panelu
        $r->addRoute('GET', '/panel/users', [PanelController::class, 'users', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('GET', '/panel/countdowns', [PanelController::class, 'countdowns', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('GET', '/panel/announcements', [PanelController::class, 'announcements', 'middleware' => [AuthMiddleware::class]]);
        $r->addRoute('GET', '/panel/modules', [PanelController::class, 'modules', 'middleware' => [AuthMiddleware::class]]);

        // 3. Trasy wyświetlacza (display)
        $r->addRoute('GET', '/display/departure', [DisplayController::class, 'getDepartures']);
        $r->addRoute('GET', '/display/announcement', [DisplayController::class, 'getAnnouncements']);
        $r->addRoute('GET', '/display/weather', [DisplayController::class, 'getWeather']);
        $r->addRoute('GET', '/display/countdown', [DisplayController::class, 'getCountdown']);
        $r->addRoute('GET', '/display/event', [DisplayController::class, 'getEvents']);
        $r->addRoute('GET', '/display/quote', [DisplayController::class, 'getQuote']);
        $r->addRoute('GET', '/display/word', [DisplayController::class, 'getWord']);

        // 4. Trasy ogólnodostępne (public)
        $r->addRoute('GET', '/propose', [HomeController::class, 'proposeAnnouncement']);
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
        $response = $container->get(ErrorController::class)->notFound();
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $response = $container->get(ErrorController::class)->methodNotAllowed();
        break;

    case FastRoute\Dispatcher::FOUND:
        $handlerData = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_array($handlerData)) {
            $controllerClass = $handlerData[0];
            $methodName = $handlerData[1];
            $middlewares = $handlerData['middleware'] ?? [];

            $controller = $container->get($controllerClass);

            if ($uri === '/stream') {
                $controller->$methodName($vars);
                exit;
            } else {
                $pipeline = new MiddlewarePipeline();
                $pipeline->add($container->get(RequestContextMiddleware::class));
                $pipeline->add($container->get(ExceptionMiddleware::class));

                foreach ($middlewares as $mwClass) {
                    $pipeline->add($container->get($mwClass));
                }

                $pipeline->add($container->get(LocaleMiddleware::class));
                $pipeline->add($container->get(CsrfMiddleware::class));

                $response = $pipeline->run($request, fn() => $controller->$methodName($vars));
            }
        } elseif (is_callable($handlerData)) {
            $handlerData($vars);
            exit;
        } else {
            throw new RuntimeException('Handler must be array [class, method] or callable');
        }
        break;

    default:
        error_log('Unknown dispatcher status: ' . $routeInfo[0]);
        $response = $container->get(ErrorController::class)->internalServerError();
        break;
}

if (isset($response)) {
    http_response_code($response->getStatusCode());

    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
    }

    echo $response->getBody();
}
