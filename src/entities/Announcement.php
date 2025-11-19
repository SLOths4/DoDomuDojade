<?php

namespace src\entities;

use DateTimeImmutable;

readonly class Announcement {
    public function __construct(
        public ?int $id,
        public string $title,
        public string $text,
        public DateTimeImmutable $date,
        public DateTimeImmutable $validUntil,
        public int $userId,
    ){}
}