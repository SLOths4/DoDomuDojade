<?php

namespace App\Infrastructure\Security;

use App\Infrastructure\Helper\SessionHelper;

class AuthenticationService
{

    public function isUserLoggedIn(): bool {
        if (!SessionHelper::has('user_id')) {
            return false;
        }

        if (!SessionHelper::validateFingerprint()) {
            $this->logout();
            return false;
        }

        return true;
    }

    public function getCurrentUserId(): ?int {
        return SessionHelper::get('user_id');
    }

    public function logout(): void {
        SessionHelper::destroy();
    }
}