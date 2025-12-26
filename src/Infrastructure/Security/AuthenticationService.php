<?php

namespace App\Infrastructure\Security;

use App\Domain\Entity\User;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Repository\UserRepository;
use Exception;

readonly class AuthenticationService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

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

    /**
     * @throws Exception
     */
    public function getCurrentUser(): ?User
    {
        $userId = $this->getCurrentUserId();

        if (!$userId) {
            return null;
        }

        return $this->userRepository->findById($userId);
    }

    public function getCurrentUserId(): ?int {
        return SessionHelper::get('user_id');
    }

    public function logout(): void {
        SessionHelper::destroy();
    }
}