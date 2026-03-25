<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Shared\DomainExceptionCodes;
use App\Domain\Shared\DomainException;

final class ForbiddenSelfDeleteException extends DomainException
{
    public function __construct(int $userId)
    {
        parent::__construct(
            message: 'user.cannot_delete_self',
            errorCode: DomainExceptionCodes::USER_CANNOT_DELETE_SELF->value,
            httpStatusCode: 403,
            context: ['user_id' => $userId]
        );
    }
}
