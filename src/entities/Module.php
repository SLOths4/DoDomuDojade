<?php

namespace src\entities;

use DateTimeImmutable;

readonly class Module {
    public function __construct(
        public ?int              $id,
        public string            $moduleName,
        public bool              $isActive,
        public DateTimeImmutable $startTime,
        public DateTimeImmutable $endTime,
    ){}
}