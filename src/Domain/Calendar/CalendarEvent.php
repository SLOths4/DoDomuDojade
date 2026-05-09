<?php
declare(strict_types=1);

namespace App\Domain\Calendar;

readonly class CalendarEvent
{
    public function __construct(
        public string $summary,
        public string $description,
        public string $start,
        public string $end,
    ) {}
}
