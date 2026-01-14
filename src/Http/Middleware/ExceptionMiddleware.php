<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\DomainException;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\Translation\Translator;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

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
        } catch (DomainException $e) {
            $this->logger->error(
                sprintf('Domain Exception: %s', $e->getMessage()),
                [
                    'errorCode' => $e->errorCode,
                    'statusCode' => $e->httpStatusCode,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            if ($this->isJsonRequest($request)) {
                return $this->jsonResponse($e->httpStatusCode, [
                    'error' => $this->translator->translate($e->getMessage()),
                    'code' => $e->errorCode,
                ]);
            }

            $this->flashMessenger->flash('error', $this->translator->translate($e->getMessage()));
            return $this->redirectResponse($request->getHeaderLine('Referer') ?? '/');
        }
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
