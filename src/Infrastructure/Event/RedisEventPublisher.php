<?php
declare(strict_types=1);

namespace App\Infrastructure\Event;

use App\Domain\Event\EventPublisher;
use App\Domain\Shared\DomainEvent;
use App\Infrastructure\Persistence\PDOEventStore;
use Exception;
use Predis\Client;
use Predis\Connection\ConnectionException;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class RedisEventPublisher implements EventPublisher
{
    public function __construct(
        private Client $redis,
        private PDOEventStore $eventStore,
        private LoggerInterface $logger,
    ) {}

    /**
     * @throws EventPublishingException
     */
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

            if (!$eventJson) {
                throw EventPublishingException::serializationFailed(
                    new Exception('JSON encoding failed')
                );
            }

            $this->logger->debug("Publishing event: " . $event->getEventType());

            try {
                $this->eventStore->store($eventData);
            } catch (Throwable $e) {
                throw EventPublishingException::storageFailed($e);
            }

            try {
                $this->redis->rpush('sse:broadcast', $eventJson);

                $sseType = $this->mapToSseType($event->getEventType());
                if ($sseType) {
                    $this->redis->rpush('sse:broadcast', json_encode(['type' => $sseType]));
                }
            } catch (ConnectionException $e) {
                $this->logger->warning(
                    "Redis push failed (connection): " . $e->getMessage(),
                    ['event_type' => $event->getEventType()]
                );
            } catch (Throwable $e) {
                $this->logger->warning(
                    "Redis push failed: " . $e->getMessage(),
                    ['event_type' => $event->getEventType()]
                );
            }

            $this->logger->info(sprintf('Event published: %s', $event->getEventType()));

        } catch (Throwable $e) {
            throw EventPublishingException::publishingFailed($event->getEventType(), $e);
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