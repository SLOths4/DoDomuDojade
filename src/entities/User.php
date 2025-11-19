<?php

namespace src\entities;

use DateTimeImmutable;

readonly class User {
    public function __construct(
        public ?int              $id,
        public string            $username,
        public string            $passwordHash,
        public DateTimeImmutable $createdAt,
    ){}
}