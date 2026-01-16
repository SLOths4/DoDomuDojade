<?php

namespace App\Infrastructure\Event;

use App\Domain\Event\EventPublisher;
use App\Domain\Shared\DomainEvent;
use App\Infrastructure\Persistence\PDOEventStore;
use Exception;
use Predis\Client;
use Psr\Log\LoggerInterface;

readonly class RedisEventPublisher implements EventPublisher
{
    public function __construct(
        private Client $redis,
        private PDOEventStore $eventStore,
        private LoggerInterface $logger,
    ) {}

    public function publishAll(array $events): void
    {
        foreach ($events as $event) {
            $this->publish($event);
        }
    }
    public function publish(DomainEvent $event): void
    {
        try {
            $eventData = $this->serializeEvent($event);
            $eventJson = json_encode($eventData);

            $this->logger->debug("Publishing event: " . $event->getEventType());

            if (!$eventJson) {
                throw new Exception('Failed to serialize event');
            }

            $this->eventStore->store($eventData);

            // Publikujemy pełne zdarzenie dla ewentualnych innych konsumentów
            $this->redis->publish('sse:broadcast', $eventJson);

            // Publikujemy uproszczony komunikat dla frontendu (kompatybilność wsteczna)
            $sseType = $this->mapToSseType($event->getEventType());
            if ($sseType) {
                $this->redis->publish('sse:broadcast', json_encode(['type' => $sseType]));
            }

            $this->logger->info(
                sprintf('Event published: %s', $event->getEventType())
            );

        } catch (Exception $e) {
            error_log("❌ Publish error: " . $e->getMessage());
        }
    }

    private function mapToSseType(string $eventType): ?string
    {
        return match ($eventType) {
            'announcement.created', 'announcement.updated', 'announcement.deleted', 'announcement.approved', 'announcement.proposed' => 'announcements_updated',
            'countdown.created', 'countdown.updated', 'countdown.deleted' => 'countdown_updated',
            'module.updated', 'module.toggled' => 'modules_updated',
            default => null,
        };
    }

    private function serializeEvent(DomainEvent $event): array
    {
        return $event->toArray();
    }
}
