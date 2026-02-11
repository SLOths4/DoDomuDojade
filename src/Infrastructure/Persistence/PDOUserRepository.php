<?php

namespace App\Infrastructure\Persistence;

use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Database\DatabaseService;
use DateTimeImmutable;
use Exception;
use PDO;

/**
 * @inheritDoc
 */
readonly class PDOUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private DatabaseService $dbHelper,
        private string          $TABLE_NAME,
        private string          $DATE_FORMAT,
    ) {}

    /**
     * Maps database row to User entity.
     * @param array $row
     * @return User
     * @throws Exception
     */
    private function mapRow(array $row): User
    {
        return new User(
            (int)$row['id'],
            (string)$row['username'],
            (string)$row['password_hash'],
            new DateTimeImmutable($row['created_at'])
        );
    }

    /**
     * @inheritDoc
     */
    public function findAll(): array
    {
        $rows = $this->dbHelper->getAll("SELECT * FROM \"$this->TABLE_NAME\"");
        return array_map(fn($row) => $this->mapRow($row), $rows);
    }

    /**
     * @inheritDoc
     */
    public function findByExactUsername(string $username): ?User
    {
        $rows = $this->dbHelper->getAll(
            "SELECT * FROM \"$this->TABLE_NAME\" WHERE username = :username LIMIT 1",
            [':username' => [$username, PDO::PARAM_STR]]
        );

        if (empty($rows)) {
            return null;
        }

        return $this->mapRow($rows[0]);
    }

    /**
     * @inheritDoc
     */
    public function findByUsernamePartial(string $username): array
    {
        $rows = $this->dbHelper->getAll(
            "SELECT * FROM \"$this->TABLE_NAME\" WHERE username LIKE :username",
            [':username' => ["%$username%", PDO::PARAM_STR]]
        );

        return array_map(fn($row) => $this->mapRow($row), $rows);
    }

    /**
     * @inheritDoc
     */
    public function findById(int $id): User
    {
        $row = $this->dbHelper->getOne(
            "SELECT * FROM \"$this->TABLE_NAME\" WHERE id = :id",
            [':id' => [$id, PDO::PARAM_INT]]
        );

        if ($row === null) {
            throw new Exception("User with ID $id not found");
        }

        return $this->mapRow($row);
    }

    /**
     * @inheritDoc
     */
    public function add(User $user): int
    {
        return $this->dbHelper->insert(
            $this->TABLE_NAME,
            [
                'username'      => [$user->username, PDO::PARAM_STR],
                'password_hash' => [$user->passwordHash, PDO::PARAM_STR],
                'created_at'    => [$user->createdAt->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function update(User $user): bool
    {
        $affected = $this->dbHelper->update(
            $this->TABLE_NAME,
            [
                'username'      => [$user->username, PDO::PARAM_STR],
                'password_hash' => [$user->passwordHash, PDO::PARAM_STR],
            ],
            [
                'id' => [$user->id, PDO::PARAM_INT],
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
    public function updatePassword(int $id, string $newPasswordHash): bool
    {
        $affected = $this->dbHelper->update(
            $this->TABLE_NAME,
            [
                'password_hash' => [$newPasswordHash, PDO::PARAM_STR],
            ],
            [
                'id' => [$id, PDO::PARAM_INT],
            ]
        );

        return $affected > 0;
    }
}