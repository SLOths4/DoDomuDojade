<?php

namespace src\security;

use src\infrastructure\helpers\SessionHelper;

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