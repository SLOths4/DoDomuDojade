<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Exception\ValidationException;
use App\Http\Context\RequestContext;
use App\Infrastructure\Service\CsrfTokenService;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Random\RandomException;

final readonly class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CsrfTokenService $csrfTokenService,
        private RequestContext   $requestContext,
    ) {}

    public function handle(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        try {
            $csrf = $this->csrfTokenService->getOrCreate();
            $this->requestContext->set('csrf_token', $csrf);

            $isPostRequest = $_SERVER['REQUEST_METHOD'] === 'POST';
            if ($isPostRequest) {
                $this->hasToken($_POST);
                $providedToken = $_POST['_token'];

                if (!$this->csrfTokenService->validate($providedToken)) {
                    throw ValidationException::invalidCsrf();
                }
            }

            return $next($request);

        } catch (RandomException $e) {
            error_log('CSRF token generation failed: ' . $e->getMessage());

            return new Response(500, [], 'Internal Server Error');
        }
    }

    private function hasToken($post): void
    {
        if (empty($post['_token'])) {
            throw ValidationException::missingCsrf();
        }
    }
}