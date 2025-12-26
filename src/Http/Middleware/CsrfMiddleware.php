<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Exception\ValidationException;
use App\Http\Context\RequestContext;
use App\Infrastructure\Service\CsrfTokenService;
use GuzzleHttp\Psr7\Request;
use Random\RandomException;

/**
 * CsrfMiddleware - Protects against Cross-Site Request Forgery attacks.
 * Validates CSRF tokens on all POST requests.
 */
final readonly class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * @param CsrfTokenService $csrfTokenService
     * @param RequestContext $requestContext
     */
    public function __construct(
        private CsrfTokenService $csrfTokenService,
        private RequestContext   $requestContext,
    ) {}

    /**
     * Validates CSRF token on POST requests.
     *
     * @param callable $next Next middleware/controller in a pipeline
     * @throws ValidationException When a CSRF token is invalid or missing
     */
    public function handle(Request $request, callable $next): void
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
        } catch (RandomException $e) {
            error_log('CSRF token generation failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Internal Server Error');
        }
        $next($request);
    }

    /**
     * @throws ValidationException
     */
    private function hasToken($post): void
    {
        if (empty($post['_token'])) {
            throw ValidationException::missingCsrf();
        }
    }
}
