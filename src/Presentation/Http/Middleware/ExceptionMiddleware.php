<?php
declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use App\Application\Shared\ApplicationException;
use App\Domain\Shared\AuthenticationException;
use App\Domain\Shared\DomainException;
use App\Infrastructure\Shared\InfrastructureException;
use App\Presentation\Http\Shared\FlashMessengerInterface;
use App\Presentation\Http\Shared\MiddlewareInterface;
use App\Presentation\Http\Shared\Translator;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class ExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private FlashMessengerInterface $flashMessenger,
        private Translator $translator,
    ) {}

    public function handle(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        try {
            return $next($request);
        } catch (AuthenticationException $e) {
            return $this->handleAuthenticationException($request, $e);
        } catch (DomainException $e) {
            return $this->handleDomainException($request, $e);
        } catch (ApplicationException $e) {
            return $this->handleApplicationException($request, $e);
        } catch (InfrastructureException $e) {
            return $this->handleInfrastructureException($request, $e);
        } catch (Throwable $e) {
            return $this->handleUnexpectedException($request, $e);
        }
    }

    private function handleDomainException(ServerRequestInterface $request, DomainException $e): ResponseInterface
    {
        $this->logException($request, $e->httpStatusCode >= 500 ? 'error' : 'info', 'Domain exception', [
            'context' => $e->context,
        ], $e);

        return $this->respond(
            $request,
            $this->translator->translate($e->getMessage(), $e->context),
            $e->errorCode,
            $e->httpStatusCode,
            $e->context,
        );
    }

    private function handleAuthenticationException(
        ServerRequestInterface $request,
        AuthenticationException $e
    ): ResponseInterface {
        $this->logException($request, 'info', 'Authentication exception', ['context' => $e->context], $e);

        return $this->respond(
            $request,
            $this->translator->translate($e->getMessage(), $e->context),
            $e->errorCode,
            $e->httpStatusCode,
            $e->context,
            '/login'
        );
    }

    private function handleApplicationException(ServerRequestInterface $request, ApplicationException $e): ResponseInterface
    {
        $this->logException($request, 'error', 'Application exception', [], $e);

        return $this->respond($request, 'An error occurred', $e->errorCode, $e->httpStatusCode);
    }

    private function handleInfrastructureException(ServerRequestInterface $request, InfrastructureException $e): ResponseInterface
    {
        $this->logException($request, 'error', 'Infrastructure exception', [], $e);

        return $this->respond($request, 'An error occurred', $e->errorCode, $e->httpStatusCode);
    }

    private function handleUnexpectedException(ServerRequestInterface $request, Throwable $e): ResponseInterface
    {
        $this->logException($request, 'critical', 'Unexpected exception', ['trace' => $e->getTraceAsString()], $e);

        return $this->respond($request, 'An unexpected error occurred', 'INTERNAL_SERVER_ERROR', 500);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function respond(
        ServerRequestInterface $request,
        string $message,
        string $code,
        int $statusCode,
        array $context = [],
        ?string $redirectPath = null
    ): ResponseInterface {
        $requestId = $this->extractRequestId($request);

        if ($this->isJsonRequest($request)) {
            return new Response(
                $statusCode,
                ['Content-Type' => 'application/json', 'X-Request-Id' => $requestId],
                json_encode([
                    'error' => [
                        'code' => $code,
                        'message' => $message,
                        'context' => $context,
                    ],
                    'request_id' => $requestId,
                ])
            );
        }

        $this->flashMessenger->flash('error', $message);

        $referer = $request->getHeaderLine('Referer');
        $redirectPath = $redirectPath ?? ($referer ?: '/');

        return new Response(302, ['Location' => $redirectPath, 'X-Request-Id' => $requestId]);
    }

    /**
     * @param array<string, mixed> $extraContext
     */
    private function logException(
        ServerRequestInterface $request,
        string $level,
        string $logMessage,
        array $extraContext = [],
        ?Throwable $exception = null
    ): void {
        $context = array_merge([
            'error_code' => $this->resolveErrorCode($exception),
            'context' => $extraContext['context'] ?? [],
            'request_id' => $this->extractRequestId($request),
            'trace_id' => $request->getHeaderLine('X-Trace-Id') ?: $this->extractRequestId($request),
        ], $extraContext, [
            'exception' => $exception !== null ? get_class($exception) : null,
            'message' => $exception?->getMessage(),
            'file' => $exception?->getFile(),
            'line' => $exception?->getLine(),
        ]);

        unset($context['exception']);

        $this->logger->log($level, $logMessage, $context);
    }

    private function resolveErrorCode(?Throwable $exception): string
    {
        if ($exception instanceof DomainException) {
            return $exception->errorCode;
        }

        if ($exception instanceof ApplicationException || $exception instanceof InfrastructureException) {
            return $exception->errorCode;
        }

        return 'INTERNAL_SERVER_ERROR';
    }

    private function extractRequestId(ServerRequestInterface $request): string
    {
        $headerRequestId = $request->getHeaderLine('X-Request-Id');
        if ($headerRequestId !== '') {
            return $headerRequestId;
        }

        $attributeRequestId = $request->getAttribute('request_id');
        if (is_string($attributeRequestId) && $attributeRequestId !== '') {
            return $attributeRequestId;
        }

        return bin2hex(random_bytes(8));
    }

    private function isJsonRequest(ServerRequestInterface $request): bool
    {
        $accept = $request->getHeaderLine('Accept');
        $contentType = $request->getHeaderLine('Content-Type');
        $xRequested = $request->getHeaderLine('X-Requested-With');

        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || $xRequested === 'XMLHttpRequest';
    }
}
