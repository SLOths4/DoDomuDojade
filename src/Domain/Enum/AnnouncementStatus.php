<?php

namespace App\Domain\Enum;

use App\Domain\Exception\AnnouncementException;

/**
 * Available announcement statuses
 */
enum AnnouncementStatus: string {
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    /**
     * Transforms given value into one of the available statuses
     * @param string|int $value
     * @return self
     * @throws AnnouncementException
     */
    public static function fromString(string|int $value): self
    {
        $stringValue = strtoupper((string)$value);

        foreach (self::cases() as $case) {
            if ($case->name === $stringValue || $case->value === $stringValue) {
                return $case;
            }
        }

        throw AnnouncementException::invalidStatus($value);
    }
}
