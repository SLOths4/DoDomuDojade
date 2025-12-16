<?php
declare(strict_types=1);

use Psr\Log\LoggerInterface;
use App\Http\Controller\ErrorController;
use App\Infrastructure\Container;

function registerErrorHandling(Container $container): void
{
    $env = getenv('APP_ENV') ?: 'prod';
    $isDev = ($env === 'dev');

    ini_set('display_errors', $isDev ? '1' : '0');
    ini_set('display_startup_errors', $isDev ? '1' : '0');
    error_reporting(E_ALL);

    try {
        $logger = $container->get(LoggerInterface::class);

        set_error_handler(static function (int $severity, string $message, string $file, int $line) use ($logger): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            $logger->error('PHP error', compact('severity','message','file','line'));
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $e) use ($container, $logger, $isDev) {
            try {
                $logger->error('Uncaught exception', [
                    'type' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);

                http_response_code(500);

                $wantsJson =
                    (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
                    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
                    || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

                if ($wantsJson) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $isDev ? $e->getMessage() : 'Internal Server Error',
                        'code' => 'INTERNAL_SERVER_ERROR',
                    ]);
                } else {
                    try {
                        /** @var ErrorController $errorController */
                        $errorController = $container->get(ErrorController::class);
                        $errorController->internalServerError();
                    } catch (Throwable) {
                        header('Content-Type: text/plain; charset=utf-8');
                        echo $isDev
                            ? "Unhandled exception: " . $e->getMessage()
                            : "Internal Server Error";
                    }
                }
            } catch (Throwable $handlerError) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=utf-8');
                echo $isDev
                    ? "Fatal error in exception handler: " . $handlerError->getMessage()
                    : "Internal Server Error";
            }
        });

        register_shutdown_function(static function () use ($container, $isDev, $logger) {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                try {
                    $logger->critical('Shutdown fatal error', $error);
                } catch (Throwable) {
                    // ignoruj problemy z loggerem przy shutdown
                }

                http_response_code(500);

                $wantsJson =
                    (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
                    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
                    || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

                if ($wantsJson) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $isDev ? ($error['message'] ?? 'Fatal error') : 'Internal Server Error',
                        'code' => 'INTERNAL_SERVER_ERROR',
                    ]);
                } else {
                    try {
                        /** @var ErrorController $errorController */
                        $errorController = $container->get(ErrorController::class);
                        $errorController->internalServerError();
                    } catch (Throwable) {
                        header('Content-Type: text/plain; charset=utf-8');
                        echo $isDev ? 'Fatal error (shutdown)' : 'Internal Server Error';
                    }
                }
            }
        });
    } catch (Throwable $bootstrapError) {
        error_log("❌ CRITICAL ERROR IN registerErrorHandling: " . $bootstrapError->getMessage());
        error_log("Stack trace: " . $bootstrapError->getTraceAsString());
        set_exception_handler(static function (Throwable $e) use ($isDev) {
            http_response_code(500);
            $wantsJson =
                (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
                || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
                || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

            if ($wantsJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $isDev ? $e->getMessage() : 'Internal Server Error',
                    'code' => 'INTERNAL_SERVER_ERROR',
                ]);
            } else {
                header('Content-Type: text/plain; charset=utf-8');
                echo $isDev ? ('Unhandled exception: ' . $e->getMessage()) : 'Internal Server Error';
            }
        });

        register_shutdown_function(static function () use ($isDev) {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                http_response_code(500);
                $wantsJson =
                    (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
                    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
                    || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

                if ($wantsJson) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $isDev ? ($error['message'] ?? 'Fatal error') : 'Internal Server Error',
                        'code' => 'INTERNAL_SERVER_ERROR',
                    ]);
                } else {
                    header('Content-Type: text/plain; charset=utf-8');
                    echo $isDev ? 'Fatal error (shutdown)' : 'Internal Server Error';
                }
            }
        });

        if ($isDev) {
            error_log('registerErrorHandling failed: ' . $bootstrapError->getMessage());
        }
    }
}