<?php

namespace src\entities;

use DateTimeImmutable;

readonly class Countdown
{
    public function __construct(
        public ?int              $id,
        public string            $title,
        public DateTimeImmutable $countTo,
        public int               $userId,
    ){}
}