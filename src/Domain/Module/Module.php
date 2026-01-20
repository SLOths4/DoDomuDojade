<?php

namespace App\Domain\Module;

use DateTimeImmutable;

/**
 * Module entity
 */
class Module {
    public function __construct(
        public ?int              $id,
        public ModuleName        $moduleName,
        public bool              $isActive,
        public DateTimeImmutable $startTime,
        public DateTimeImmutable $endTime,
    ){}

    /**
     * Changes isActive field
     * @return void
     */
    public function toggle(): void
    {
        $this->isActive = !$this->isActive;
    }
}