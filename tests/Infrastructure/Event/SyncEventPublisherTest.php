<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Event;

use App\Domain\Event\EventStoreRepositoryInterface;
use App\Domain\Shared\DomainEvent;
use App\Infrastructure\Event\SyncEventPublisher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class SyncEventPublisherTest extends TestCase
{
    public function testPublishDeduplicatedEventDoesNotThrow(): void
    {
        $store = new class implements EventStoreRepositoryInterface {
            public function append(DomainEvent $event): bool
            {
                return false;
            }
        };

        $publisher = new SyncEventPublisher(
            $store,
            $this->createMock(LoggerInterface::class),
            3,
        );

        $publisher->publish($this->createTestEvent());

        self::assertTrue(true);
    }

    public function testPublishRetriesAndEventuallySucceeds(): void
    {
        $attempt = 0;

        $store = new class($attempt) implements EventStoreRepositoryInterface {
            public int $attempt = 0;

            public function __construct(int $attempt)
            {
                $this->attempt = $attempt;
            }

            public function append(DomainEvent $event): bool
            {
                ++$this->attempt;

                if ($this->attempt < 3) {
                    throw new RuntimeException('temporary error');
                }

                return true;
            }
        };

        $publisher = new SyncEventPublisher(
            $store,
            $this->createMock(LoggerInterface::class),
            3,
        );

        $publisher->publish($this->createTestEvent());

        self::assertSame(3, $store->attempt);
    }

    public function testPublishThrowsAfterMaxRetries(): void
    {
        $store = new class implements EventStoreRepositoryInterface {
            public function append(DomainEvent $event): bool
            {
                throw new RuntimeException('permanent error');
            }
        };

        $publisher = new SyncEventPublisher(
            $store,
            $this->createMock(LoggerInterface::class),
            2,
        );

        $this->expectException(RuntimeException::class);

        $publisher->publish($this->createTestEvent());
    }

    private function createTestEvent(): DomainEvent
    {
        return new class('agg-1', 'announcement') extends DomainEvent {
            public function getEventType(): string
            {
                return 'announcement.created';
            }

            protected function getPayload(): array
            {
                return ['test' => true];
            }
        };
    }
}
