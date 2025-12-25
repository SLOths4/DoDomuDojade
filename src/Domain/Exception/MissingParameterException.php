<?php

namespace App\Domain\Exception;

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
            code: 400,
            context: ['parameter' => $paramName, 'location' => $context],
            previous: $previous
        );
    }
}
