<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Countdown\Countdown;
use App\Domain\Countdown\CountdownRepositoryInterface;
use App\Infrastructure\Database\DatabaseService;
use DateTimeImmutable;
use Exception;
use PDO;

/**
 * @inheritDoc
 */
readonly class PDOCountdownRepository implements CountdownRepositoryInterface
{
    public function __construct(
        private DatabaseService $dbHelper,
        private string          $TABLE_NAME,
        private string          $DATE_FORMAT,
    ) {}

    /**
     * Maps database row to Countdown entity.
     * @param array $row
     * @return Countdown
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
     * @inheritDoc
     */
    public function findById(int $id): ?Countdown
    {
        $row = $this->dbHelper->getOne(
            "SELECT * FROM $this->TABLE_NAME WHERE id = :id",
            [':id' => [$id, PDO::PARAM_INT]]
        );

        return $row !== null ? $this->mapRow($row) : null;
    }

    /**
     * @inheritDoc
     */
    public function findCurrent(): ?Countdown
    {
        $row = $this->dbHelper->getOne(
            "SELECT * FROM $this->TABLE_NAME WHERE count_to > :now ORDER BY count_to LIMIT 1",
            [':now' => [date($this->DATE_FORMAT), PDO::PARAM_STR]]
        );

        return $row !== null ? $this->mapRow($row) : null;
    }

    /**
     * @inheritDoc
     */
    public function findAll(): array
    {
        $rows = $this->dbHelper->getAll("SELECT * FROM $this->TABLE_NAME");
        return array_map(fn($r) => $this->mapRow($r), $rows);
    }

    /**
     * @inheritDoc
     */
    public function add(Countdown $countdown): int
    {
        return $this->dbHelper->insert(
            $this->TABLE_NAME,
            [
                'title'    => [$countdown->title, PDO::PARAM_STR],
                'count_to' => [$countdown->countTo->format($this->DATE_FORMAT), PDO::PARAM_STR],
                'user_id'  => [$countdown->userId, PDO::PARAM_INT],
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function update(Countdown $countdown): bool
    {
        $affected = $this->dbHelper->update(
            $this->TABLE_NAME,
            [
                'title'    => [$countdown->title, PDO::PARAM_STR],
                'count_to' => [$countdown->countTo->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ],
            [
                'id' => [$countdown->id, PDO::PARAM_INT],
            ]
        );

        return $affected > 0;
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): bool
    {
        $affected = $this->dbHelper->delete(
            $this->TABLE_NAME,
            [
                'id' => [$id, PDO::PARAM_INT],
            ]
        );

        return $affected > 0;
    }

    /**
     * @inheritDoc
     */
    public function updateField(int $id, string $field, string $value): bool
    {
        $affected = $this->dbHelper->update(
            $this->TABLE_NAME,
            [
                $field => [$value, PDO::PARAM_STR],
            ],
            [
                'id' => [$id, PDO::PARAM_INT],
            ]
        );

        return $affected > 0;
    }
}