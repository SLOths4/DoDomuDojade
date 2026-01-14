<?php
declare(strict_types=1);

use App\Domain\Shared\DomainException;
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

    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);

    $logger = $container->get(LoggerInterface::class);

    // ✅ Określ content type raz
    $detectContentType = static function (): string {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $xRequested = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        if (str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || $xRequested === 'XMLHttpRequest'
        ) {
            return 'json';
        }
        return 'html';
    };

    $renderJsonError = static function (
        Throwable $e,
        bool $isDev,
        LoggerInterface $logger
    ): void {
        $statusCode = 500;
        $responseBody = [
            'type' => 'https://api.dodomudojade.local/errors/internal-error',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => $isDev ? $e->getMessage() : 'An unexpected error occurred',
        ];

        if ($e instanceof DomainException) {
            $statusCode = $e->httpStatusCode;
            $responseBody = $e->toArray();
        }

        header('Content-Type: application/problem+json');
        http_response_code($statusCode);
        echo json_encode($responseBody, JSON_THROW_ON_ERROR);
    };

    $renderHtmlError = static function (
        Throwable $e,
        bool $isDev,
        Container $container
    ): void {
        $statusCode = $e instanceof DomainException ? $e->httpStatusCode : 500;

        try {
            http_response_code($statusCode);
            $errorController = $container->get(ErrorController::class);

            if ($statusCode === 404) {
                $errorController->notFound();
            } elseif ($statusCode === 403) {
                $errorController->forbidden();
            } else {
                $errorController->internalServerError();
            }
        } catch (Throwable) {
            header('Content-Type: text/plain; charset=utf-8');
            echo $isDev ? "Fatal Error: " . $e->getMessage() : "Internal Server Error";
        }
    };

    // Error handler - konwertuje PHP errors na Exceptions
    set_error_handler(static function (int $severity, string $message, string $file, int $line) use ($logger): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        $logger->error('PHP Error', compact('severity', 'message', 'file', 'line'));
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    // Exception handler
    set_exception_handler(static function (Throwable $e) use ($logger, $isDev, $detectContentType, $renderJsonError, $renderHtmlError, $container): void {
        // Log zawsze
        $context = [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];

        if ($e instanceof DomainException) {
            $context['errorCode'] = $e->errorCode;
            $context['statusCode'] = $e->httpStatusCode;
            $context['context'] = $e->context;
            $logger->warning('Domain Exception: ' . $e->getMessage(), $context);
        } else {
            $logger->critical('Uncaught Exception: ' . $e->getMessage(), $context);
        }

        // Renderuj w zależności od content type
        $contentType = $detectContentType();

        if ($contentType === 'json') {
            $renderJsonError($e, $isDev, $logger);
        } else {
            $renderHtmlError($e, $isDev, $container);
        }
        exit;
    });

    // Fatal error handler
    register_shutdown_function(static function () use ($logger, $isDev, $detectContentType, $renderJsonError, $renderHtmlError, $container): void {
        $error = error_get_last();

        if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        $exception = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        $logger->critical('Fatal Error', $error);

        $contentType = $detectContentType();
        if ($contentType === 'json') {
            $renderJsonError($exception, $isDev, $logger);
        } else {
            $renderHtmlError($exception, $isDev, $container);
        }
    });
}
