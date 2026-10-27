<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Domain\Calendar\CalendarServiceInterface;
use App\Infrastructure\ExternalApi\Calendar\CalendarApiException;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Provides events data formatted for display page
 */
readonly class GetDisplayEventsUseCase
{
    /**
     * @param CalendarServiceInterface $calendarService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CalendarServiceInterface $calendarService,
        private LoggerInterface $logger
    ) {}

    /**
     * @return array<int, array{summary: string, description: string, start: string, end: string}>|null
     */
    public function execute(): ?array
    {
        $this->logger->debug('Fetching calendar events for display');

        try {
            $events = $this->calendarService->getEvents();
            $eventsArray = [];

            foreach ($events as $event) {
                $eventsArray[] = [
                    'summary' => $event->summary,
                    'description' => $event->description,
                    'start' => $event->start,
                    'end' => $event->end,
                ];
            }

            $this->logger->debug('Calendar events fetched for display', [
                'event_count' => count($eventsArray),
            ]);

            return $eventsArray;
        } catch (CalendarApiException|Exception $e) {
            $this->logger->error('Error processing calendar data', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
