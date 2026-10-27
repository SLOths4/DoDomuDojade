<?php

namespace App\Infrastructure\Security;

use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Helper\SessionHelper;
use Exception;

/**
 * AuthenticationService used for user authentication
 */
readonly class AuthenticationService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Checks if there is a user logged in the session
     * @param string $remoteAddr
     * @param string $userAgent
     * @return bool
     */
    public function isUserLoggedIn(string $remoteAddr, string $userAgent): bool {
        if (!SessionHelper::has('user_id')) {
            return false;
        }

        if (!SessionHelper::validateFingerprint($remoteAddr, $userAgent)) {
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