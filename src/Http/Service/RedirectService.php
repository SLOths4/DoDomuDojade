<?php
declare(strict_types=1);

namespace App\Http\Service;

use App\Domain\Announcement\AnnouncementException;
use App\Domain\Exception\AuthenticationException;
use App\Domain\Exception\CountdownException;
use App\Domain\Exception\ValidationException;
use App\Domain\Module\ModuleException;
use App\Domain\User\UserException;
use App\Http\Context\RequestContext;
use Throwable;

final readonly class RedirectService
{
    private const array ADMIN_SCOPE_REDIRECTS = [
        AuthenticationException::class => '/login',
        UserException::class => '/panel/users',
        AnnouncementException::class => '/panel/announcements',
        CountdownException::class => '/panel/countdowns',
        ModuleException::class => '/panel/modules',
        ValidationException::class => '/panel',
    ];

    private const array USER_SCOPE_REDIRECTS = [
        AuthenticationException::class => '/login',
        AnnouncementException::class => '/propose',
        ValidationException::class => '/propose',
    ];

    public function __construct(
        private RequestContext $requestContext
    ) {}

    public function getRedirectPath(Throwable $exception, string $currentPath, ?string $referer = null): string
    {
        $scope = $this->requestContext->get('scope', 'user');

        $redirectMap = match ($scope) {
            'admin' => self::ADMIN_SCOPE_REDIRECTS,
            default => self::USER_SCOPE_REDIRECTS,
        };

        foreach ($redirectMap as $exceptionClass => $path) {
            if ($exception instanceof $exceptionClass) {
                return $path;
            }
        }

        return $referer ?: ($currentPath ?: '/');
    }
}
