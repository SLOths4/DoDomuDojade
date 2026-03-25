<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Infrastructure\ExternalApi\Calendar\CalendarService;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Provides events data formatted for display page
 */
readonly class GetDisplayEventsUseCase
{
    /**
     * @param CalendarService $calendarService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CalendarService $calendarService,
        private LoggerInterface $logger
    ) {}

    /**
     * @return array<int, array{summary: string, description: string, start: string, end: string}>|null
     */
    public function execute(): ?array
    {
        try {
            $events = $this->calendarService->getEvents();
            $eventsArray = [];

            foreach ($events->getItems() as $event) {
                $eventsArray[] = [
                    'summary' => $event->getSummary() ?? "Wydarzenie bez tytułu",
                    'description' => $event->getDescription() ?? "Wydarzenie bez opisu",
                    'start' => ($event->getStart()->dateTime ?? null) !== null ? new DateTime(($event->getStart()->dateTime))->format('d.m.Y H:i') : new DateTime(($event->getStart()->date))->format('d.m.Y'),
                    'end' => ($event->getEnd()->dateTime ?? null) !== null ? new DateTime(($event->getEnd()->dateTime))->format('H:i') : new DateTime(($event->getEnd()->date))->format('d.m.Y'),
                ];
            }

            return $eventsArray;
        } catch (Exception $e) {
            $this->logger->error('Error processing calendar data', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
