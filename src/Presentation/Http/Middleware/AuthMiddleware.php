<?php
declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use App\Domain\Shared\AuthenticationException;
use App\Infrastructure\Security\AuthenticationService;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
     * @param ServerRequestInterface $request
     * @param callable $next
     * @return ResponseInterface
     * @throws AuthenticationException When a user is not authenticated
     */
    public function handle(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        if (!$this->authService->isUserLoggedIn()) {
            throw AuthenticationException::noUserLoggedIn();
        }

        $user = $this->authService->getCurrentUser();
        $this->requestContext->setCurrentUser($user);

        return $next($request);
    }
}
