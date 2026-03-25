<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use Throwable;

class EntityNotFoundException extends DomainException
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = 'entity.not_found',
        string $errorCode = 'ENTITY_NOT_FOUND',
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, 404, $context, $previous);
    }
}
