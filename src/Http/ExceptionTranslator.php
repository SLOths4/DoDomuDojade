<?php

namespace App\Http;

use App\Domain\Announcement\AnnouncementException;
use App\Domain\Exception\AuthenticationException;
use App\Domain\Exception\CountdownException;
use App\Domain\Exception\ValidationException;
use App\Domain\Module\ModuleException;
use App\Domain\Shared\DomainException;
use App\Domain\User\UserException;
use App\Http\Context\RequestContext;

final readonly class ExceptionTranslator
{
    /**
     * POST routes → GET display routes (admin panel)
     */
    private const array ADMIN_SCOPE_REDIRECTS = [
        // Authentications
        AuthenticationException::class => '/login',

        // Users: /panel/add_user, /panel/delete_user → /panel/users
        UserException::class => '/panel/users',

        // Announcements: /panel/add_announcement, /panel/edit_announcement → /panel/announcements
        AnnouncementException::class => '/panel/announcements',

        // Countdowns: /panel/add_countdown, /panel/edit_countdown → /panel/countdowns
        CountdownException::class => '/panel/countdowns',

        // Modules: /panel/edit_module, /panel/toggle_module → /panel/modules
        ModuleException::class => '/panel/modules',

        ValidationException::class => '/panel',
    ];

    /**
     * User-facing pages
     */
    private const array USER_SCOPE_REDIRECTS = [
        AuthenticationException::class => '/login',
        AnnouncementException::class => '/propose',
        ValidationException::class => '/propose',
    ];


    public function getRedirectPath(DomainException $exception, string $currentPath): string
    {
        $scope = RequestContext::getInstance()->get('scope', 'user');

        $redirectMap = match ($scope) {
            'admin' => self::ADMIN_SCOPE_REDIRECTS,
            default => self::USER_SCOPE_REDIRECTS,
        };

        return $redirectMap[$exception::class] ?? $currentPath;
    }
}
