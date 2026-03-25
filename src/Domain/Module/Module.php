<?php

namespace App\Domain\Module;

use App\Domain\Module\Event\ModuleToggledEvent;
use App\Domain\Module\Event\ModuleUpdatedEvent;
use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

/**
 * Module entity
 */
class Module {
    /** @var DomainEvent[] */
    private array $events = [];

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
        $this->events[] = new ModuleToggledEvent((string)$this->id, $this->isActive);
    }

    public function updateSchedule(DateTimeImmutable $startTime, DateTimeImmutable $endTime): void
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->events[] = new ModuleUpdatedEvent((string)$this->id, $this->startTime, $this->endTime);
    }

    /**
     * @return DomainEvent[]
     */
    public function getDomainEvents(): array
    {
        return $this->events;
    }

    public function clearDomainEvents(): void
    {
        $this->events = [];
    }
}
