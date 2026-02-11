<?php

namespace App\Domain\Module\Event;

use App\Domain\Shared\DomainEvent;

class ModuleToggledEvent extends DomainEvent
{
    private string $moduleId;
    private bool $isActive;

    /**
     * Constructor
     *
     * @param string $moduleId ID of module being approved
     * @param bool $isActive
     */
    public function __construct(
        string $moduleId,
        bool $isActive,
    ) {
        parent::__construct(
            aggregateId: $moduleId,
            aggregateType: 'Module'
        );

        $this->moduleId = $moduleId;
        $this->isActive = $isActive;
    }

    /**
     * @inheritDoc
     */
    public function getEventType(): string
    {
        return 'module.toggled';
    }

    /**
     * @inheritDoc
     */
    protected function getPayload(): array
    {
        return [
            'moduleId' => $this->moduleId,
            'isActive' => $this->isActive,
        ];
    }
}