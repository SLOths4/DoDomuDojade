<?php

namespace App\Domain\Entity;

use DateTimeImmutable;

/**
 * Countdown entity
 */
readonly class Countdown
{
    public function __construct(
        public ?int              $id,
        public string            $title,
        public DateTimeImmutable $countTo,
        public int               $userId,
    ){}
}