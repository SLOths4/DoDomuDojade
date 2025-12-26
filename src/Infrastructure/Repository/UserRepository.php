<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entity\User;
use App\Infrastructure\Helper\DatabaseHelper;
use DateTimeImmutable;
use Exception;
use PDO;
use ReflectionClass;

readonly class UserRepository
{
    public function __construct(
        private DatabaseHelper $dbHelper,
        private string         $TABLE_NAME,
        private string         $DATE_FORMAT,
    ) {}

    /**
     * Zwraca dozwolone pola do update'u na podstawie encji (wszystkie poza id i createdAt).
     * @return array
     */
    public function getAllowedFields(): array
    {
        $reflection = new ReflectionClass(User::class);
        $properties = $reflection->getProperties();

        $fields = [];
        foreach ($properties as $prop) {
            $name = $prop->getName();
            if ($name !== 'id' && $name !== 'createdAt') {
                $fields[] = $name;
            }
        }
        return $fields;
    }

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
     * Returns all users.
     * @return User[]
     * @throws Exception
     */
    public function findAll(): array
    {
        $rows = $this->dbHelper->getAll("SELECT * FROM \"$this->TABLE_NAME\"");
        return array_map(fn($row) => $this->mapRow($row), $rows);
    }

    /**
     * Find the exact user by username.
     * @throws Exception
     */
    public function findByUsername(string $username): ?User
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
     * Find users by partial username.
     * @throws Exception
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
     * Returns a single user by ID.
     * @param int $id
     * @return User
     * @throws Exception
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
     * Returns a single user by exact username.
     * @param string $username
     * @return User|null
     * @throws Exception
     */
    public function findByExactUsername(string $username): ?User
    {
        $row = $this->dbHelper->getOne(
            "SELECT * FROM \"$this->TABLE_NAME\" WHERE username = :username",
            [':username' => [$username, PDO::PARAM_STR]]
        );

        return $row === null ? null : $this->mapRow($row);
    }

    /**
     * Adds a user.
     * @param User $user
     * @return bool
     * @throws Exception
     */
    public function add(User $user): bool
    {
        $lastId = $this->dbHelper->insert(
            $this->TABLE_NAME,
            [
                'username'      => [$user->username, PDO::PARAM_STR],
                'password_hash' => [$user->passwordHash, PDO::PARAM_STR],
                'created_at'    => [$user->createdAt->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ]
        );

        return !empty($lastId);
    }

    /**
     * Updates a user.
     * @param User $user
     * @return bool
     * @throws Exception
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
     * Deletes a user.
     * @param int $id
     * @return bool
     * @throws Exception
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
     * Updates user password.
     * @param int $id
     * @param string $newPasswordHash
     * @return bool
     * @throws Exception
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