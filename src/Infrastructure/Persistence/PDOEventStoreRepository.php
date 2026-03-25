<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Event\EventStoreRepositoryInterface;
use App\Domain\Shared\DomainEvent;
use App\Infrastructure\Database\DatabaseService;
use PDO;

readonly class PDOEventStoreRepository implements EventStoreRepositoryInterface
{
    public function __construct(
        private DatabaseService $dbHelper,
        private string $tableName,
    ) {}

    public function append(DomainEvent $event): bool
    {
        $eventData = $event->toArray();

        $query = "INSERT INTO {$this->tableName} (event_id, event_type, payload, occurred_at)\n"
            . 'VALUES (:event_id, :event_type, :payload, :occurred_at) '
            . 'ON CONFLICT (event_id) DO NOTHING';

        $statement = $this->dbHelper->executeStatement($query, [
            ':event_id' => [$eventData['eventId'], PDO::PARAM_STR],
            ':event_type' => [$eventData['eventType'], PDO::PARAM_STR],
            ':payload' => [json_encode($eventData, JSON_THROW_ON_ERROR), PDO::PARAM_STR],
            ':occurred_at' => [$eventData['occurredAt'], PDO::PARAM_STR],
        ]);

        return $statement->rowCount() === 1;
    }
}
