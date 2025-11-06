<?php

namespace src\repository;

use DateTimeImmutable;
use Exception;
use PDO;
use Psr\Log\LoggerInterface;
use src\core\Model;
use src\entities\Countdown;

class CountdownRepository extends Model
{
    public function __construct(
        PDO $pdo,
        LoggerInterface $logger,
        private readonly string $TABLE_NAME,
        private readonly string $DATE_FORMAT,
    ) {
        parent::__construct($pdo, $logger);
        $this->logger->info('Countdown repository using table: ' . $this->TABLE_NAME);
    }

    /**
     * @throws Exception
     */
    private function mapRow(array $row): Countdown
    {
        return new Countdown(
            (int)$row['id'],
            (string)$row['title'],
            new DateTimeImmutable($row['count_to']),
            (int)$row['user_id']
        );
    }

    /**
     * @throws Exception
     */
    public function findById(int $id): ?Countdown
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->TABLE_NAME} WHERE id = :id");
        
        $this->bindParams($stmt, [':id' => [$id, PDO::PARAM_INT]]);
        
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return !empty($rows) ? $this->mapRow($rows[0]) : null;
    }

    /**
     * @throws Exception
     */
    public function findCurrent(): ?Countdown
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->TABLE_NAME} WHERE count_to > :now ORDER BY count_to LIMIT 1");
        
        $this->bindParams($stmt, [':now' => [date($this->DATE_FORMAT), PDO::PARAM_STR]]);
        
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return !empty($rows) ? $this->mapRow($rows[0]) : null;
    }

    /**
     * @throws Exception
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->TABLE_NAME}");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(fn($r) => $this->mapRow($r), $rows);
    }

    /**
     * Adds a countdown.
     * @param Countdown $countdown
     * @return bool
     * @throws Exception
     */
    public function add(Countdown $countdown): bool
    {
        $this->logger->debug('Adding countdown', ['countdown' => $countdown]);

        $stmt = $this->pdo->prepare("INSERT INTO {$this->TABLE_NAME} (title, count_to, user_id) VALUES (:title, :count_to, :user_id)");

        $this->bindParams($stmt, [
            ':title' => [$countdown->title, PDO::PARAM_STR],
            ':count_to' => [$countdown->countTo->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ':user_id' => [$countdown->userId, PDO::PARAM_INT],
        ]);

        $success = $stmt->execute();
        $this->logger->info("Countdown insert " . ($success ? "successful" : "failed"));

        return $success && $stmt->rowCount() > 0;
    }

    /**
     * Updates a countdown.
     * @param Countdown $countdown
     * @return bool
     * @throws Exception
     */
    public function update(Countdown $countdown): bool
    {
        $this->logger->debug('Updating countdown', ['countdown' => $countdown]);

        $stmt = $this->pdo->prepare("UPDATE {$this->TABLE_NAME} SET title = :title, count_to = :count_to WHERE id = :id");

        $this->bindParams($stmt, [
            ':id' => [$countdown->id, PDO::PARAM_INT],
            ':title' => [$countdown->title, PDO::PARAM_STR],
            ':count_to' => [$countdown->countTo->format($this->DATE_FORMAT), PDO::PARAM_STR],
        ]);

        $success = $stmt->execute();
        $this->logger->info("Countdown update " . ($success ? "successful" : "failed"));

        return $success && $stmt->rowCount() > 0;
    }

    /**
     * Deletes a countdown.
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $this->logger->debug('Deleting countdown', ['id' => $id]);

        $stmt = $this->pdo->prepare("DELETE FROM {$this->TABLE_NAME} WHERE id = :id");

        $this->bindParams($stmt, [':id' => [$id, PDO::PARAM_INT]]);

        $success = $stmt->execute();
        $this->logger->info("Countdown delete " . ($success ? "successful" : "failed"));

        return $success && $stmt->rowCount() > 0;
    }

    /**
     * Updates a specific field in a countdown.
     * @param int $id
     * @param string $field
     * @param string $value
     * @return bool
     * @throws Exception
     */
    public function updateField(int $id, string $field, string $value): bool
    {
        $this->logger->debug('Updating countdown field', ['id' => $id, 'field' => $field]);

        $query = "UPDATE {$this->TABLE_NAME} SET $field = :value WHERE id = :id";
        $stmt = $this->pdo->prepare($query);

        $this->bindParams($stmt, [
            ':value' => [$value, PDO::PARAM_STR],
            ':id' => [$id, PDO::PARAM_INT],
        ]);

        $success = $stmt->execute();
        $this->logger->info("Countdown field update " . ($success ? "successful" : "failed"));

        return $success && $stmt->rowCount() > 0;
    }
}
