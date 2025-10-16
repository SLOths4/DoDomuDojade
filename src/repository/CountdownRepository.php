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
        $rows = $this->executeStatement(
            "SELECT * FROM {$this->TABLE_NAME} WHERE id = :id",
            [':id' => [$id, PDO::PARAM_INT]]
        );
        return $this->mapRow($rows[0]);
    }

    /**
     * @throws Exception
     */
    public function findCurrent(): ?Countdown
    {
        $rows = $this->executeStatement(
            "SELECT * FROM {$this->TABLE_NAME} WHERE count_to > :now ORDER BY count_to LIMIT 1",
            [':now' => [date('Y-m-d H:i:s'), PDO::PARAM_STR]]
        );
        return $this->mapRow($rows[0]);
    }

    /**
     * @throws Exception
     */
    public function findAll(): array
    {
        $stmt = $this->executeStatement("SELECT * FROM {$this->TABLE_NAME}");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => $this->mapRow($r), $rows);
    }

    /**
     * @throws Exception
     */
    public function add(Countdown $countdown): bool
    {
        $this->logger->debug('Adding countdown', ['countdown' => $countdown]);

        return $this->executeStatement(
                "INSERT INTO {$this->TABLE_NAME} (title, count_to, user_id) VALUES (:title, :count_to, :user_id)",
                [
                    ':title' => [$countdown->title, PDO::PARAM_STR],
                    ':count_to' => [$countdown->countTo->format('Y-m-d H:i:s'), PDO::PARAM_STR],
                    ':user_id' => [$countdown->userId, PDO::PARAM_INT],
                ]
            ) != false;
    }

    /**
     * @throws Exception
     */
    public function update(Countdown $countdown): bool
    {
        $this->logger->debug('Updating countdown', ['countdown' => $countdown]);

        return $this->executeStatement(
                "UPDATE {$this->TABLE_NAME} SET title = :title, count_to = :count_to WHERE id = :id",
                [
                    ':id' => [$countdown->id, PDO::PARAM_INT],
                    ':title' => [$countdown->title, PDO::PARAM_STR],
                    ':count_to' => [$countdown->countTo->format('Y-m-d H:i:s'), PDO::PARAM_STR],
                ]
            ) != false;
    }

    /**
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $this->logger->debug('Deleting countdown', ['id' => $id]);
        return $this->executeStatement(
                "DELETE FROM {$this->TABLE_NAME} WHERE id = :id",
                [':id' => [$id, PDO::PARAM_INT]]
            ) != false;
    }

    /**
     * @throws Exception
     */
    public function updateField(int $id, string $field, string $value): bool
    {
        $query = "UPDATE {$this->TABLE_NAME} SET $field = :value WHERE id = :id";
        return $this->executeStatement(
                $query,
                [
                    ':value' => [$value, PDO::PARAM_STR],
                    ':id' => [$id, PDO::PARAM_INT]
                ]
            ) != false;
    }
}

