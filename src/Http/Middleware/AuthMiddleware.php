<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Exception\AuthenticationException;
use App\Infrastructure\Security\AuthenticationService;
use GuzzleHttp\Psr7\Request;

/**
 * AuthMiddleware - Ensures only authenticated users can access protected routes.
 * Throws UserException if a user is not logged in.
 */
final readonly class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @param AuthenticationService $authService Checks if user has valid session
     */
    public function __construct(
        private AuthenticationService $authService
    ) {}

    /**
     * Validates user authentication before proceeding.
     *
     * @param Request $request
     * @param callable $next
     * @throws AuthenticationException When a user is not authenticated
     */
    public function handle(Request $request, callable $next): void
    {
        if (!$this->authService->isUserLoggedIn()) {
            throw AuthenticationException::noUserLoggedIn();
        }

        $next($request);
    }
}
