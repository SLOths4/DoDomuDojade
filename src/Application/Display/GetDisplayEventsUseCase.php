<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Infrastructure\Service\CalendarService;
use Psr\Log\LoggerInterface;
use Exception;
use DateTime;

readonly class GetDisplayEventsUseCase
{
    public function __construct(
        private CalendarService $calendarService,
        private LoggerInterface $logger
    ) {}

    public function execute(): ?array
    {
        try {
            $events = $this->calendarService->getEvents();
            $eventsArray = [];

            foreach ($events->getItems() as $event) {
                $eventsArray[] = [
                    'summary' => $event->getSummary() === null ? "Wydarzenie bez tytułu" : $event->getSummary(),
                    'description' => $event->getDescription() === null ? "Wydarzenie bez opisu" : $event->getDescription(),
                    'start' => isset($event->getStart()->dateTime) === true ? new DateTime(($event->getStart()->dateTime))->format('H:i') : "Wydarzenie całodniowe",
                    'end' => isset($event->getEnd()->dateTime) === true ? new DateTime(($event->getEnd()->dateTime))->format('H:i') : null,
                ];
            }

            return $eventsArray;
        } catch (Exception $e) {
            $this->logger->error('Error processing calendar data', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
