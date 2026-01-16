<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Shared\ApplicationException;
use App\Domain\Shared\DomainException;
use App\Http\Service\RedirectService;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\Translation\Translator;
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
        private RedirectService $redirectService,
    ) {}

    public function handle(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        try {
            return $next($request);
        } catch (DomainException $e) {
            return $this->handleException($request, $e, $e->httpStatusCode, $e->errorCode);
        } catch (ApplicationException $e) {
            return $this->handleException($request, $e, $e->getHttpStatusCode());
        } catch (Throwable $e) {
            throw $e;
        }
    }

    private function handleException(ServerRequestInterface $request, Throwable $e, int $statusCode, int $errorCode = 0): ResponseInterface
    {
        $this->logger->error(
            sprintf('%s: %s', get_class($e), $e->getMessage()),
            [
                'errorCode' => $errorCode,
                'statusCode' => $statusCode,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]
        );

        if ($this->isJsonRequest($request)) {
            return $this->jsonResponse($statusCode, [
                'error' => $this->translator->translate($e->getMessage()),
                'code' => $errorCode,
                'status' => $statusCode,
            ]);
        }

        $this->flashMessenger->flash('error', $this->translator->translate($e->getMessage()));

        $redirectPath = $this->redirectService->getRedirectPath(
            $e,
            $request->getUri()->getPath(),
            $request->getHeaderLine('Referer')
        );

        return $this->redirectResponse($redirectPath);
    }

    private function jsonResponse(int $statusCode, array $data): ResponseInterface
    {
        $response = new Response($statusCode);
        $response->getBody()->write(json_encode($data));

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }

    private function redirectResponse(string $location): ResponseInterface
    {
        return new Response(302, ['Location' => $location]);
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
