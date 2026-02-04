<?php
declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use App\Domain\Shared\ValidationException;
use App\Infrastructure\Service\CsrfTokenService;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\MiddlewareInterface;
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

            $mutatingMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
            $requestMethod = $request->getMethod();
            $isMutatingRequest = in_array($requestMethod, $mutatingMethods, true);
            if ($isMutatingRequest) {
                $providedToken = $request->getHeaderLine('X-CSRF-Token') ?:
                    $request->getHeaderLine('X-XSRF-TOKEN');

                if (!$providedToken) {
                    $parsedBody = $request->getParsedBody();
                    if (is_array($parsedBody) && !empty($parsedBody['_token'])) {
                        $providedToken = $parsedBody['_token'];
                    } elseif (!empty($_POST['_token'])) {
                        $providedToken = $_POST['_token'];
                    }
                }

                // Jeśli brak tokenu, wyrzuć błąd
                if (empty($providedToken)) {
                    throw ValidationException::missingCsrf();
                }

                // Waliduj token
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
}
