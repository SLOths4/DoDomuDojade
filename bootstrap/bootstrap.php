<?php
declare(strict_types=1);

use App\Infrastructure\Container;
use App\Infrastructure\Container\Providers\AnnouncementProvider;
use App\Infrastructure\Container\Providers\DisplayProvider;
use App\Infrastructure\Container\Providers\SharedProvider;
use App\Infrastructure\Container\Providers\UserProvider;

ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../vendor/autoload.php';

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    error_log(sprintf('PHP Error [%d]: %s in %s:%d', $errno, $errstr, $errfile, $errline));
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

register_shutdown_function(function (): void {
    $error = error_get_last();

    if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    error_log(sprintf('Fatal Error [%d]: %s in %s:%d', $error['type'], $error['message'], $error['file'], $error['line']));

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal server error', 'code' => 'INTERNAL_SERVER_ERROR']);
});

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$container = new Container();

$providers = [
    new SharedProvider(),
    new UserProvider(),
    new AnnouncementProvider(),
    new DisplayProvider(),
];

foreach ($providers as $provider) {
    $provider->register($container);
}

return $container;
