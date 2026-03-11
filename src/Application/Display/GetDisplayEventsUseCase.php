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
     * @return array|null
     */
    public function execute(): ?array
    {
        try {
            $events = $this->calendarService->getEvents();
            $eventsArray = [];

            foreach ($events->getItems() as $event) {
                $eventsArray[] = [
                    'summary' => $event->getSummary() === null ? "Wydarzenie bez tytułu" : $event->getSummary(),
                    'description' => $event->getDescription() === null ? "Wydarzenie bez opisu" : $event->getDescription(),
                    'start' => isset($event->getStart()->dateTime) === true ? new DateTime(($event->getStart()->dateTime))->format('d.m.Y H:i') : new DateTime(($event->getStart()->date))->format('d.m.Y'),
                    'end' => isset($event->getEnd()->dateTime) === true ? new DateTime(($event->getEnd()->dateTime))->format('H:i') : new DateTime(($event->getEnd()->date))->format('d.m.Y'),
                ];
            }

            return $eventsArray;
        } catch (Exception $e) {
            $this->logger->error('Error processing calendar data', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
