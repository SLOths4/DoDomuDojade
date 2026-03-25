<?php

declare(strict_types=1);

namespace App\Infrastructure\Event;

use App\Domain\Event\EventPublisher;
use App\Domain\Event\EventStoreRepositoryInterface;
use App\Domain\Shared\DomainEvent;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

readonly class SyncEventPublisher implements EventPublisher
{
    public function __construct(
        private EventStoreRepositoryInterface $eventStore,
        private LoggerInterface $logger,
        private int $maxRetries = 3,
    ) {}

    public function publish(DomainEvent $event): void
    {
        $eventId = $event->toArray()['eventId'] ?? 'unknown';
        $attempt = 0;

        do {
            ++$attempt;

            try {
                $stored = $this->eventStore->append($event);

                if ($stored) {
                    return;
                }

                $this->logger->warning('Event deduplicated in event store', [
                    'event_id' => $eventId,
                    'attempt' => $attempt,
                ]);

                return;
            } catch (Throwable $exception) {
                $this->logger->error('Event publication attempt failed', [
                    'event_id' => $eventId,
                    'attempt' => $attempt,
                    'error' => $exception->getMessage(),
                ]);

                if ($attempt >= $this->maxRetries) {
                    throw new RuntimeException(
                        sprintf('Failed to publish event %s after %d attempts', $eventId, $attempt),
                        0,
                        $exception
                    );
                }

                usleep($attempt * 100000);
            }
        } while ($attempt < $this->maxRetries);
    }

    public function publishAll(array $events): void
    {
        foreach ($events as $event) {
            $this->publish($event);
        }
    }
}
