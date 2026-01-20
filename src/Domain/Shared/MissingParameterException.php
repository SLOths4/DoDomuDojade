<?php

namespace App\Domain\Shared;

use Throwable;

/**
 * Required parameter missing
 */
final class MissingParameterException extends ValidationException
{
    public function __construct(
        string $paramName,
        string $context = '',
        ?Throwable $previous = null
    ) {
        parent::__construct(
            message: sprintf('Missing required parameter: %s%s', $paramName, $context ? " ($context)" : ''),
            errorCode: DomainExceptionCodes::MISSING_PARAMETER->value,
            context: ['parameter' => $paramName, 'location' => $context],
            previous: $previous
        );
    }
}
