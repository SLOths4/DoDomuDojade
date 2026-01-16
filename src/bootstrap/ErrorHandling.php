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

    // ✅ Start output buffering to prevent "headers already sent"
    ob_start();

    $logger = $container->get(LoggerInterface::class);

    // ✅ Określ content type raz
    $detectContentType = static function (): string {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $xRequested = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        if (str_contains($accept, 'text/event-stream')) {
            return 'sse';
        }

        if (str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || $xRequested === 'XMLHttpRequest'
        ) {
            return 'json';
        }
        return 'html';
    };

    $renderSseError = static function (
        Throwable $e,
        bool $isDev
    ): void {
        // SSE errors should NOT clean the buffer if we want to keep the connection open, 
        // but here we are in the exception handler which will EXIT anyway.
        // For SSE, we usually just want to send the error event.
        if (!headers_sent()) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
        }

        $statusCode = 500;
        if ($e instanceof DomainException) {
            $statusCode = $e->httpStatusCode;
        } elseif ($e instanceof \App\Application\Shared\ApplicationException) {
            $statusCode = $e->getHttpStatusCode();
        }

        echo "event: error\n";
        $data = [
            'message' => ($isDev || $e instanceof DomainException || $e instanceof \App\Application\Shared\ApplicationException) 
                ? $e->getMessage() 
                : 'Internal stream error',
            'code' => $e->getCode(),
            'status' => $statusCode
        ];

        if ($isDev) {
            $data['exception'] = get_class($e);
            $data['file'] = $e->getFile();
            $data['line'] = $e->getLine();
            $data['trace'] = $e->getTraceAsString();
        }

        echo "data: " . json_encode($data) . "\n\n";
        flush();
    };

    $renderJsonError = static function (
        Throwable $e,
        bool $isDev,
        LoggerInterface $logger
    ): void {
        if (ob_get_level()) {
            ob_clean();
        }

        $statusCode = 500;
        $title = 'Internal Server Error';
        $detail = $isDev ? $e->getMessage() : 'An unexpected error occurred';
        $errorCode = $e->getCode();

        if ($e instanceof DomainException) {
            $statusCode = $e->httpStatusCode;
            $detail = $e->getMessage();
            $title = 'Domain Error';
            $errorCode = $e->errorCode;
        } elseif ($e instanceof \App\Application\Shared\ApplicationException) {
            $statusCode = $e->getHttpStatusCode();
            $detail = $e->getMessage();
            $title = 'Application Error';
        }

        $responseBody = [
            'type' => 'https://api.dodomudojade.local/errors/' . ($statusCode === 500 ? 'internal-error' : 'error'),
            'title' => $title,
            'status' => $statusCode,
            'detail' => $detail,
            'code' => $errorCode,
        ];

        if ($isDev) {
            $responseBody['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ];
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
        if (ob_get_level()) {
            ob_clean();
        }

        $statusCode = 500;
        if ($e instanceof DomainException) {
            $statusCode = $e->httpStatusCode;
        } elseif ($e instanceof \App\Application\Shared\ApplicationException) {
            $statusCode = $e->getHttpStatusCode();
        }

        try {
            http_response_code($statusCode);
            $errorController = $container->get(App\Http\Controller\ErrorController::class);

            if ($statusCode === 404) {
                $errorController->notFound();
            } elseif ($statusCode === 403) {
                $errorController->forbidden();
            } elseif ($statusCode === 401) {
                // Jeśli 401 w HTML, to pewnie sesja wygasła - przekieruj na login
                header('Location: /login');
                exit;
            } else {
                $errorController->internalServerError();
                if ($isDev) {
                    echo "<div style='padding: 20px; background: #fee; border: 1px solid #f00; margin: 20px;'>";
                    echo "<h3>Debug Info (isDev=true)</h3>";
                    echo "<p><b>Exception:</b> " . get_class($e) . "</p>";
                    echo "<p><b>Message:</b> " . $e->getMessage() . "</p>";
                    echo "<p><b>File:</b> " . $e->getFile() . " on line " . $e->getLine() . "</p>";
                    echo "<pre>" . $e->getTraceAsString() . "</pre>";
                    echo "</div>";
                }
            }
        } catch (Throwable) {
            header('Content-Type: text/plain; charset=utf-8');
            echo $isDev ? "Fatal Error: " . $e->getMessage() . "\n\n" . $e->getTraceAsString() : "Internal Server Error";
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
    set_exception_handler(static function (Throwable $e) use ($logger, $isDev, $detectContentType, $renderJsonError, $renderHtmlError, $renderSseError, $container): void {
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

        if ($contentType === 'sse') {
            $renderSseError($e, $isDev);
        } elseif ($contentType === 'json') {
            $renderJsonError($e, $isDev, $logger);
        } else {
            $renderHtmlError($e, $isDev, $container);
        }
        exit;
    });

    // Fatal error handler
    register_shutdown_function(static function () use ($logger, $isDev, $detectContentType, $renderJsonError, $renderHtmlError, $renderSseError, $container): void {
        $error = error_get_last();

        if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        $exception = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        $logger->critical('Fatal Error', $error);

        $contentType = $detectContentType();
        if ($contentType === 'sse') {
            $renderSseError($exception, $isDev);
        } elseif ($contentType === 'json') {
            $renderJsonError($exception, $isDev, $logger);
        } else {
            $renderHtmlError($exception, $isDev, $container);
        }
    });
}
