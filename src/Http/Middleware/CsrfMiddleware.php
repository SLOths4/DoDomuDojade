<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Infrastructure\Security\CsrfService;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Exception\ValidationException;

/**
 * CsrfMiddleware - Protects against Cross-Site Request Forgery attacks.
 * Validates CSRF tokens on all POST requests.
 */
readonly class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * @param CsrfService $csrfService Validates CSRF token pairs
     */
    public function __construct(
        private CsrfService $csrfService
    ) {}

    /**
     * Validates CSRF token on POST requests.
     *
     * @param callable $next Next middleware/controller in a pipeline
     * @throws ValidationException When a CSRF token is invalid or missing
     */
    public function handle(callable $next): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            $sessionToken = SessionHelper::get('csrf_token', '');

            if (!$this->csrfService->validateCsrf($token, $sessionToken)) {
                throw ValidationException::invalidCsrf();
            }
        }

        $next();
    }
}
