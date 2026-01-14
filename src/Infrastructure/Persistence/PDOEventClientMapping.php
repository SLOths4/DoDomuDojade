<?php
namespace App\Infrastructure\Persistence;

use PDO;
use DateTime;

readonly class PDOEventClientMapping
{
    public function __construct(private PDO $pdo) {}

    /**
     * Record that an event was sent to a client
     */
    public function recordEventSentToClient(string $eventId, string $clientId): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO event_client_mapping (event_id, client_id, published_at)
            VALUES (:event_id, :client_id, NOW())
            ON CONFLICT (event_id, client_id)
            DO UPDATE SET published_at = NOW()
        ');

        $stmt->execute([
            ':event_id' => $eventId,
            ':client_id' => $clientId,
        ]);
    }

    /**
     * Record multiple clients receiving an event
     */
    public function recordEventSentToClients(string $eventId, array $clientIds): void
    {
        foreach ($clientIds as $clientId) {
            $this->recordEventSentToClient($eventId, $clientId);
        }
    }

    /**
     * Get all events sent to a specific client
     * Useful for: Resuming SSE stream if client reconnects
     */
    public function getClientEventHistory(string $clientId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                e.event_id,
                e.event_type,
                e.payload,
                e.occurred_at,
                ecm.published_at
            FROM event_client_mapping ecm
            INNER JOIN events e ON ecm.event_id = e.event_id
            WHERE ecm.client_id = :client_id
            ORDER BY ecm.published_at DESC
            LIMIT :limit
        ');

        $stmt->bindValue(':client_id', $clientId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all events NOT yet sent to a client
     * Useful for: Catching client up on missed events
     */
    public function getUnsentEventsForClient(string $clientId, DateTime $since): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                e.event_id,
                e.event_type,
                e.payload,
                e.occurred_at
            FROM events e
            LEFT JOIN event_client_mapping ecm 
                ON e.event_id = ecm.event_id 
                AND ecm.client_id = :client_id
            WHERE e.occurred_at >= :since
                AND ecm.id IS NULL
            ORDER BY e.occurred_at ASC
        ');

        $stmt->execute([
            ':client_id' => $clientId,
            ':since' => $since->format('Y-m-d H:i:s'),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get how many clients received a specific event
     */
    public function getClientCountForEvent(string $eventId): int
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as count
            FROM event_client_mapping
            WHERE event_id = :event_id
        ');

        $stmt->execute([':event_id' => $eventId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['count'] ?? 0);
    }

    /**
     * Get all active clients (that have received events recently)
     */
    public function getActiveClients(int $minutesAgo = 5): array
    {
        $stmt = $this->pdo->prepare('
            SELECT DISTINCT client_id
            FROM event_client_mapping
            WHERE published_at >= NOW() - INTERVAL \':minutes minutes\' 
            ORDER BY client_id
        ');

        $stmt->bindValue(':minutes', $minutesAgo, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_column($results, 'client_id');
    }

    /**
     * Clean up old mappings (older than N days)
     * Useful for: Garbage collection, preventing huge table
     */
    public function cleanupOldMappings(int $daysOld = 30): int
    {
        $stmt = $this->pdo->prepare('
            DELETE FROM event_client_mapping
            WHERE created_at < NOW() - INTERVAL \':days day\'
        ');

        $stmt->bindValue(':days', $daysOld, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Get statistics about event distribution
     */
    public function getStatistics(): array
    {
        // Total mappings
        $stmt1 = $this->pdo->query('SELECT COUNT(*) as count FROM event_client_mapping');
        $totalMappings = (int)$stmt1->fetch(PDO::FETCH_ASSOC)['count'];

        // Total unique events
        $stmt2 = $this->pdo->query('SELECT COUNT(DISTINCT event_id) as count FROM event_client_mapping');
        $totalEvents = (int)$stmt2->fetch(PDO::FETCH_ASSOC)['count'];

        // Total unique clients
        $stmt3 = $this->pdo->query('SELECT COUNT(DISTINCT client_id) as count FROM event_client_mapping');
        $totalClients = (int)$stmt3->fetch(PDO::FETCH_ASSOC)['count'];

        // Average clients per event
        $avgClientsPerEvent = $totalEvents > 0 ? round($totalMappings / $totalEvents, 2) : 0;

        return [
            'totalMappings' => $totalMappings,
            'totalEvents' => $totalEvents,
            'totalClients' => $totalClients,
            'avgClientsPerEvent' => $avgClientsPerEvent,
        ];
    }

    /**
     * Check if client already received specific event
     */
    public function hasClientReceivedEvent(string $eventId, string $clientId): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as count
            FROM event_client_mapping
            WHERE event_id = :event_id AND client_id = :client_id
        ');

        $stmt->execute([
            ':event_id' => $eventId,
            ':client_id' => $clientId,
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0) > 0;
    }
}