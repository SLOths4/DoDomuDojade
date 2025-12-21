<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Exception\UserException;

/**
 * AuthMiddleware - Ensures only authenticated users can access protected routes.
 * Throws UserException if a user is not logged in.
 */
readonly class AuthMiddleware implements MiddlewareInterface
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
     * @param callable $next Next middleware/controller in a pipeline
     * @throws UserException When a user is not authenticated
     */
    public function handle(callable $next): void
    {
        if (!$this->authService->isUserLoggedIn()) {
            throw UserException::noUserLoggedIn();
        }

        $next();
    }
}
