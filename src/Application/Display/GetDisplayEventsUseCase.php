<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Infrastructure\ExternalApi\Calendar\CalendarApiException;
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
                    'summary' => $event->getSummary(),
                    'description' => $event->getDescription(),
                    'start' => new DateTime($event->getStart()->dateTime)->format('d.m.Y H:i'),
                    'end' => new DateTime($event->getEnd()->dateTime)->format('H:i'),
                ];
            }

            return $eventsArray;
        } catch (CalendarApiException|Exception $e) {
            $this->logger->error('Error processing calendar data', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
