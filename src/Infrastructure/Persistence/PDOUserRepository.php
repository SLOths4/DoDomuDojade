<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Shared\EntityNotFoundException;
use App\Domain\Shared\DomainExceptionCodes;
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
    private const TABLE_NAME = 'user';

    public function __construct(
        private DatabaseService $dbHelper,
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
            new DateTimeImmutable($row['created_at']),
            (bool)($row['must_change_password'] ?? false)
        );
    }

    /**
     * @inheritDoc
     */
    public function findAll(): array
    {
        $rows = $this->dbHelper->getAll("SELECT * FROM \"" . self::TABLE_NAME . "\"");
        return array_map(fn($row) => $this->mapRow($row), $rows);
    }

    /**
     * @inheritDoc
     */
    public function findByExactUsername(string $username): ?User
    {
        $rows = $this->dbHelper->getAll(
            "SELECT * FROM \"" . self::TABLE_NAME . "\" WHERE username = :username LIMIT 1",
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
            "SELECT * FROM \"" . self::TABLE_NAME . "\" WHERE username LIKE :username",
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
            "SELECT * FROM \"" . self::TABLE_NAME . "\" WHERE id = :id",
            [':id' => [$id, PDO::PARAM_INT]]
        );

        if ($row === null) {
            throw new EntityNotFoundException(
                message: "user.not_found",
                errorCode: DomainExceptionCodes::USER_NOT_FOUND->value,
                context: ['user_id' => $id]
            );
        }

        return $this->mapRow($row);
    }

    /**
     * @inheritDoc
     */
    public function add(User $user): int
    {
        return $this->dbHelper->insert(
            self::TABLE_NAME,
            [
                'username'             => [$user->username, PDO::PARAM_STR],
                'password_hash'        => [$user->passwordHash, PDO::PARAM_STR],
                'must_change_password' => [$user->mustChangePassword, PDO::PARAM_BOOL],
                'created_at'           => [$user->createdAt->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function update(User $user): bool
    {
        $affected = $this->dbHelper->update(
            self::TABLE_NAME,
            [
                'username'             => [$user->username, PDO::PARAM_STR],
                'password_hash'        => [$user->passwordHash, PDO::PARAM_STR],
                'must_change_password' => [$user->mustChangePassword, PDO::PARAM_BOOL],
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
            self::TABLE_NAME,
            [
                'id' => [$id, PDO::PARAM_INT],
            ]
        );

        return $affected > 0;
    }

    /**
     * @inheritDoc
     */
    public function updatePassword(int $id, string $newPasswordHash, bool $mustChangePassword = false): bool
    {
        $affected = $this->dbHelper->update(
            self::TABLE_NAME,
            [
                'password_hash'        => [$newPasswordHash, PDO::PARAM_STR],
                'must_change_password' => [$mustChangePassword, PDO::PARAM_BOOL],
            ],
            [
                'id' => [$id, PDO::PARAM_INT],
            ]
        );

        return $affected > 0;
    }
}