<?php

namespace App\Domain\Enum;

use App\Domain\Exception\AnnouncementException;

enum AnnouncementStatus: string {
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

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
