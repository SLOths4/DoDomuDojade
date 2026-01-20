<?php
namespace App\Infrastructure\Persistence;

use PDO;

final readonly class PDOEventStore
{
    public function __construct(private PDO $pdo){}

    public function store(array $eventData): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO events (event_id, event_type, payload, occurred_at)
            VALUES (:event_id, :event_type, :payload, :occurred_at)
        ');

        $stmt->execute([
            ':event_id' => $eventData['eventId'],
            ':event_type' => $eventData['eventType'],
            ':payload' => json_encode($eventData['payload']),
            ':occurred_at' => $eventData['occurredAt'],
        ]);
    }

    public function getAll(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare('
            SELECT event_id, event_type, payload, occurred_at
            FROM events
            ORDER BY created_at DESC
            LIMIT :limit
        ');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
