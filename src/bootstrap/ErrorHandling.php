<?php
declare(strict_types=1);

use App\Domain\Exception\DomainException;
use App\Http\Controller\ErrorController;
use App\Infrastructure\Container;
use Psr\Log\LoggerInterface;

/**
 * @throws ReflectionException
 * @throws ErrorException
 */
function registerErrorHandling(Container $container): void
{
    $env = getenv('APP_ENV') ?: 'prod';
    $isDev = ($env === 'dev');

    ini_set('display_errors', $isDev ? '1' : '0');
    ini_set('display_startup_errors', $isDev ? '1' : '0');
    error_reporting(E_ALL);

    $logger = $container->get(LoggerInterface::class);

    $isJsonRequest = static function (): bool {
        return (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
            || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));
    };

    $renderError = static function (Throwable $e) use ($container, $isDev, $isJsonRequest) {
        $httpCode = 500;
        $errorCode = 'INTERNAL_SERVER_ERROR';
        $message = $isDev ? $e->getMessage() : 'Unexpected error occurred.';
        $data = [];

        if ($e instanceof DomainException) {
            $errorCode = $e->errorCode;
            $data = $e->context;
        }

        if ($isJsonRequest()) {
            header('Content-Type: application/json');
            http_response_code($httpCode);
            echo json_encode([
                'success' => false,
                'message' => $message,
                'code' => $errorCode,
                'data' => $data,
                'trace' => $isDev ? $e->getTrace() : null
            ]);
            exit;
        }

        try {
            http_code: http_response_code($httpCode);
            $errorController = $container->get(ErrorController::class);
            $errorController->internalServerError();
        } catch (Throwable) {
            header('Content-Type: text/plain; charset=utf-8');
            echo $isDev ? "Fatal Error: " . $e->getMessage() : "Internal Server Error";
        }
        exit;
    };

    set_error_handler(/**
     * @throws ErrorException
     */ static function (int $severity, string $message, string $file, int $line) use ($logger): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        $logger->error('PHP Error', compact('severity', 'message', 'file', 'line'));
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    set_exception_handler(static function (Throwable $e) use ($logger, $renderError) {
        $logger->critical('Uncaught Exception: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'context' => ($e instanceof DomainException) ? $e->context : []
        ]);

        $renderError($e);
    });

    register_shutdown_function(static function () use ($logger, $renderError) {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $exception = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            $logger->critical('Shutdown Fatal Error', $error);
            $renderError($exception);
        }
    });
}