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

            $published = $this->redis->publish('sse:broadcast', $eventJson);
            $this->logger->debug("Published to $published subscribers");

            $this->logger->info(
                sprintf('Event published: %s (%d subscribers)',
                    $event->getEventType(),
                    $published
                )
            );

        } catch (Exception $e) {
            error_log("❌ Publish error: " . $e->getMessage());
            //$this->logger->error("Failed to publish event: " . $e->getMessage());
            //throw $e;
        }
    }

    private function serializeEvent(DomainEvent $event): array
    {
        return $event->toArray();
    }
}
