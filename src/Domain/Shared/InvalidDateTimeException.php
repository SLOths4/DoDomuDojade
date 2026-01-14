<?php

namespace App\Domain\Shared;

use App\Domain\Exception\ValidationException;
use Throwable;

/**
 * Invalid date/time format
 */
final class InvalidDateTimeException extends ValidationException
{
    /**
     * @param string $value
     * @param string $field
     * @param string|null $expectedFormat
     * @param Throwable|null $previous
     */
    public function __construct(
        string $value,
        string $field,
        ?string $expectedFormat,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            message: sprintf('Invalid date/time for "%s": "%s"%s', $field, $value, $expectedFormat ? sprintf(' (expected: %s)', $expectedFormat) : ''),
            code: 400,
            context: ['field' => $field, 'value' => $value, 'format' => $expectedFormat],
            previous: $previous
        );
    }
}