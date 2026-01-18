<?php
declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use App\Application\Shared\ApplicationException;
use App\Domain\Shared\AuthenticationException;
use App\Domain\Shared\DomainException;
use App\Infrastructure\Shared\InfrastructureException;
use App\Presentation\Http\Shared\FlashMessengerInterface;
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
        }catch (AuthenticationException) {
            return new Response(302, ['Location' => '/login']);
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
        if ($e->httpStatusCode >= 500) {
            $this->logger->error('Domain exception', [
                'code' => $e->errorCode,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        } else {
            $this->logger->info('Domain exception', [
                'code' => $e->errorCode,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->respond($request, $this->translator->translate($e->getMessage()), $e->errorCode, $e->httpStatusCode);
    }

    private function handleApplicationException(ServerRequestInterface $request, ApplicationException $e): ResponseInterface
    {
        $this->logger->error('Application exception', [
            'code' => $e->errorCode,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return $this->respond($request, 'An error occurred', $e->errorCode, $e->httpStatusCode);
    }

    private function handleInfrastructureException(ServerRequestInterface $request, InfrastructureException $e): ResponseInterface
    {
        $this->logger->error('Infrastructure exception', [
            'code' => $e->errorCode,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return $this->respond($request, 'An error occurred', $e->errorCode, $e->httpStatusCode);
    }

    private function handleUnexpectedException(ServerRequestInterface $request, Throwable $e): ResponseInterface
    {
        $this->logger->critical('Unexpected exception', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        return $this->respond($request, 'An unexpected error occurred', 'INTERNAL_SERVER_ERROR', 500);
    }

    private function respond(ServerRequestInterface $request, string $message, string $code, int $statusCode): ResponseInterface
    {
        if ($this->isJsonRequest($request)) {
            return new Response(
                $statusCode,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'error' => $message,
                    'code' => $code,
                ])
            );
        }

        $this->flashMessenger->flash('error', $message);

        $referer = $request->getHeaderLine('Referer');
        $redirectPath = $referer ?: '/';

        return new Response(302, ['Location' => $redirectPath]);
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
