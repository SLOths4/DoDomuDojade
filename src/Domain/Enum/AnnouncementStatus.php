<?php

namespace App\Domain\Enum;

enum AnnouncementStatus: string {
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
}
