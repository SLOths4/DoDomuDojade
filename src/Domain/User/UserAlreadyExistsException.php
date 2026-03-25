<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Shared\DomainExceptionCodes;
use App\Domain\Shared\ValidationException;
use Throwable;

final class UserAlreadyExistsException extends ValidationException
{
    public function __construct(string $username, ?Throwable $previous = null)
    {
        parent::__construct(
            message: 'user.username_taken',
            errorCode: DomainExceptionCodes::USER_ALREADY_EXISTS->value,
            httpStatusCode: 409,
            context: ['username' => $username],
            previous: $previous
        );
    }
}
