<?php

namespace src\repository;

use DateTimeImmutable;
use Exception;
use PDO;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use src\entities\User;
use src\infrastructure\helpers\DatabaseHelper;

readonly class UserRepository
{
    /**
     * @throws Exception
     */
    public function __construct(
        private PDO            $pdo,
        private LoggerInterface        $logger,
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
        $stmt = $this->dbHelper->executeStatement("SELECT * FROM $this->TABLE_NAME");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->mapRow($row), $rows);
    }

    /**
     * Returns users by username (partial match).
     * @param string $username
     * @return User[]
     * @throws Exception
     */
    public function findByUsername(string $username): array
    {
        $stmt = $this->dbHelper->executeStatement(
            "SELECT * FROM $this->TABLE_NAME WHERE username LIKE :username",
            [':username' => ["%$username%", PDO::PARAM_STR]]
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $stmt = $this->dbHelper->executeStatement(
            "SELECT * FROM $this->TABLE_NAME WHERE id = :id",
            [':id' => [$id, PDO::PARAM_INT]]
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            throw new Exception("User with ID $id not found");
        }
        return $this->mapRow($rows[0]);
    }

    /**
     * Returns a single user by exact username.
     * @param string $username
     * @return User|null
     * @throws Exception
     */
    public function findByExactUsername(string $username): ?User
    {
        $stmt = $this->dbHelper->executeStatement(
            "SELECT * FROM $this->TABLE_NAME WHERE username = :username",
            [':username' => [$username, PDO::PARAM_STR]]
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return empty($rows) ? null : $this->mapRow($rows[0]);
    }

    /**
     * Adds a user.
     * @param User $user
     * @return bool
     * @throws Exception
     */
    public function add(User $user): bool
    {
        $this->logger->debug("Adding user", ["username" => $user->username]);
        $stmt = $this->pdo->prepare("
            INSERT INTO $this->TABLE_NAME (username, password_hash, created_at)
            VALUES (:username, :password_hash, :created_at)
        ");
        $this->dbHelper->bindParams($stmt, [
            ':username' => [$user->username, PDO::PARAM_STR],
            ':password_hash' => [$user->passwordHash, PDO::PARAM_STR],
            ':created_at' => [$user->createdAt->format($this->DATE_FORMAT), PDO::PARAM_STR],
        ]);
        $success = $stmt->execute();
        $this->logger->info("User insert " . ($success ? "successful" : "failed"), ["username" => $user->username]);
        return $success && $stmt->rowCount() > 0;
    }

    /**
     * Updates a user.
     * @param User $user
     * @return bool
     * @throws Exception
     */
    public function update(User $user): bool
    {
        $this->logger->debug("Updating user", ["id" => $user->id, "username" => $user->username]);
        $stmt = $this->pdo->prepare("
            UPDATE {$this->TABLE_NAME}
            SET username = :username, password_hash = :password_hash
            WHERE id = :id
        ");
        $this->dbHelper->bindParams($stmt, [
            ':id' => [$user->id, PDO::PARAM_INT],
            ':username' => [$user->username, PDO::PARAM_STR],
            ':password_hash' => [$user->passwordHash, PDO::PARAM_STR],
        ]);
        $success = $stmt->execute();
        $this->logger->info("User update " . ($success ? "successful" : "failed"), ["id" => $user->id]);
        return $success && $stmt->rowCount() > 0;
    }

    /**
     * Deletes a user.
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $this->logger->debug("Deleting user", ["id" => $id]);
        $stmt = $this->pdo->prepare("DELETE FROM $this->TABLE_NAME WHERE id = :id");
        $this->dbHelper->bindParams($stmt, [':id' => [$id, PDO::PARAM_INT]]);
        $success = $stmt->execute();
        $this->logger->info("User delete " . ($success ? "successful" : "failed"), ["id" => $id]);
        return $success && $stmt->rowCount() > 0;
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
        $this->logger->debug("Updating user password", ["id" => $id]);
        $stmt = $this->pdo->prepare("
            UPDATE {$this->TABLE_NAME}
            SET password_hash = :password_hash
            WHERE id = :id
        ");
        $this->dbHelper->bindParams($stmt, [
            ':id' => [$id, PDO::PARAM_INT],
            ':password_hash' => [$newPasswordHash, PDO::PARAM_STR],
        ]);
        $success = $stmt->execute();
        $this->logger->info("User password update " . ($success ? "successful" : "failed"), ["id" => $id]);
        return $success && $stmt->rowCount() > 0;
    }
}
