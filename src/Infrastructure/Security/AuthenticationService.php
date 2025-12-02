<?php

namespace App\Infrastructure\Security;

use App\Infrastructure\Helper\SessionHelper;

class AuthenticationService
{

    public function isUserLoggedIn(): bool {
        return SessionHelper::has('user_id');
    }

    public function getCurrentUserId(): ?int {
        return SessionHelper::get('user_id');
    }

    public function logout(): void {
        SessionHelper::destroy();
    }
}