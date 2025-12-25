<?php

namespace App\Domain\Entity;

use DateTimeImmutable;

class Module {
    public function __construct(
        public ?int              $id,
        public string            $moduleName,
        public bool              $isActive,
        public DateTimeImmutable $startTime,
        public DateTimeImmutable $endTime,
    ){}

    public function toggle(): void
    {
        $this->isActive = !$this->isActive;
    }
}