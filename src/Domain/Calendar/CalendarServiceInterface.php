<?php
declare(strict_types=1);

namespace App\Domain\Calendar;

/**
 * Interface for calendar services
 */
interface CalendarServiceInterface
{
    /**
     * @return CalendarEvent[]
     */
    public function getEvents(): array;
}
