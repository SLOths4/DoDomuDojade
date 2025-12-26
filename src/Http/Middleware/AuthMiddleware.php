<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Exception\AuthenticationException;
use App\Http\Context\RequestContext;
use App\Infrastructure\Security\AuthenticationService;
use Exception;
use GuzzleHttp\Psr7\Request;

/**
 * AuthMiddleware - Ensures only authenticated users can access protected routes.
 * Throws UserException if a user is not logged in.
 */
final readonly class AuthMiddleware implements MiddlewareInterface
{

    /**
     * @param AuthenticationService $authService Checks if user has valid session
     * @param RequestContext        $requestContext
     */
    public function __construct(
        private AuthenticationService $authService,
        private RequestContext        $requestContext,
    ) {}

    /**
     * Validates user authentication before proceeding.
     *
     * @param Request $request
     * @param callable $next
     * @throws AuthenticationException When a user is not authenticated
     * @throws Exception
     */
    public function handle(Request $request, callable $next): void
    {
        if (!$this->authService->isUserLoggedIn()) {
            throw AuthenticationException::noUserLoggedIn();
        }

        $user = $this->authService->getCurrentUser();
        $this->requestContext->setCurrentUser($user);

        $next($request);
    }
}
