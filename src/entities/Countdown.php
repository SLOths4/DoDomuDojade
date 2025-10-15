<?php

namespace src\entities;

use DateTimeImmutable;

readonly class Countdown
{
    public function __construct(
        public ?int $id,
        public string $name,
        public DateTimeImmutable $date,
        public int $userId,
    ){}
}