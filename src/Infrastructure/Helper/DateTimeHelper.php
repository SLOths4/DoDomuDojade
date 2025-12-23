<?php

namespace App\Infrastructure\Helper;

use DateTimeImmutable;
use Exception;

final readonly class DateTimeHelper
{
    private const array FORMATS = ['H:i:s', 'H:i', 'Y-m-d H:i:s', 'Y-m-d'];

    /**
     * @throws Exception
     */
    public static function parse(?string $value): ?DateTimeImmutable
    {
        if (empty($value)) {
            return null;
        }

        foreach (self::FORMATS as $format) {
            $result = DateTimeImmutable::createFromFormat($format, $value);
            if ($result) {
                return $result;
            }
        }

        throw new Exception("Cannot parse datetime: $value");
    }
}
