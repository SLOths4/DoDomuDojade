<?php
declare(strict_types=1);

namespace App\Presentation\Http\Middleware;

use App\Infrastructure\Security\AuthenticationService;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\MiddlewareInterface;
use GuzzleHttp\Psr7\Response;
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
     */
    public function handle(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        $serverParams = $request->getServerParams();
        $remoteAddr = (string)($serverParams['REMOTE_ADDR'] ?? '');
        $userAgent = $request->getHeaderLine('User-Agent');

        if (!$this->authService->isUserLoggedIn($remoteAddr, $userAgent)) {
            return new Response(302, ['Location' => '/login']);
        }

        $user = $this->authService->getCurrentUser();
        $this->requestContext->setCurrentUser($user);

        // Check if user must change password
        $uri = $request->getUri()->getPath();
        if ($user->mustChangePassword && 
            $uri !== '/change-password' && 
            !str_starts_with($uri, '/api/user') && 
            $uri !== '/logout'
        ) {
            return new Response(302, ['Location' => '/change-password']);
        }

        return $next($request);
    }
}
