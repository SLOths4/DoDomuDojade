<?php

namespace App\Infrastructure\Security;

use App\Domain\User\User;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Persistence\UserRepository;
use Exception;

/**
 * AuthenticationService used for user authentication
 */
readonly class AuthenticationService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Checks if there is a user logged in the session
     * @return bool
     */
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
     * Returns currently logged user
     * @return User|null
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

    /**
     * Returns current users id
     * @return int|null
     */
    public function getCurrentUserId(): ?int {
        return SessionHelper::get('user_id');
    }

    /**
     * Logs out current user
     * @return void
     */
    public function logout(): void {
        SessionHelper::destroy();
    }
}