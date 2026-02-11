<?php

namespace App\Presentation\Http\DTO;

use DateTimeImmutable;

readonly class ModuleViewDTO
{
    public function __construct(
        public ?int              $id,
        public string            $moduleName,
        public string            $moduleNameLabel,
        public bool              $isActive,
        public DateTimeImmutable $startTime,
        public DateTimeImmutable $endTime,
    ) {}
}
