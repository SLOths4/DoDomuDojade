<?php

namespace App\Infrastructure\Helper;

use App\Domain\User\UserException;

/**
 * Helper class for validating users
 */
final readonly class UserValidationHelper
{
    /**
     * @throws UserException
     */
    public function validateId(int $id): void
    {
        if ($id <= 0) {
            throw UserException::invalidId();
        }
    }
}
