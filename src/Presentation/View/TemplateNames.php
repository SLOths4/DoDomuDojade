<?php

declare(strict_types=1);

namespace App\Presentation\View;

enum TemplateNames: string
{
    // Layout
    case BASE = 'layout/base';
    case AUTH_BASE = 'layout/auth-base';
    case SIMPLE_BASE = 'layout/simple-base';

    // Pages
    case HOME = 'pages/index';
    case LOGIN = 'pages/login';
    case USERS = 'pages/users';
    case ANNOUNCEMENTS = 'pages/announcements';
    case COUNTDOWNS = 'pages/countdowns';
    case MODULES = 'pages/modules';
    case PANEL = 'pages/panel';
    case DISPLAY = 'pages/display';
    case SSE = 'pages/sse';
    case ANNOUNCEMENT_PROPOSE = 'pages/announcement-propose';

    // Errors
    case ERROR_403 = 'errors/403';
    case ERROR_404 = 'errors/404';
    case ERROR_405 = 'errors/405';
    case ERROR_500 = 'errors/500';

    // Components
    case NAVBAR = 'components/navbar';
    case FOOTER = 'components/footer';
    case ERROR_ALERT = 'components/error';
    case SUCCESS_ALERT = 'components/success';
}
