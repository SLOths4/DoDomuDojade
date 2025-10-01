<?php
namespace src\models;

use Exception;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use src\core\Model;

/**
 * Class used for operations on table-storing users in a provided database
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 */
class UserModel extends Model
{
    public function __construct(
        PDO $pdo,
        LoggerInterface $logger,
        private readonly string $TABLE_NAME,
    ) {
        parent::__construct($pdo, $logger);
        if ($this->TABLE_NAME === '') {
            $this->logger->error('Users table name is missing.');
            throw new RuntimeException('Users table name is missing.');
        }

        $this->logger->debug("Users table name being used: $this->TABLE_NAME");
    }

    /**
     * Fetches all users from the database.
     *
     * @return array Array of users in the database
     * @throws Exception
     */
    public function getUsers(): array {
        try {
            $query = "SELECT * FROM $this->TABLE_NAME ORDER BY id ASC";
            $this->logger->info("Fetching all users.");

            $users = $this->executeStatement($query);

            $sortedUsers = [];
            foreach ($users as $user) {
                $sortedUsers[$user['id']] = $user;
            }

            ksort($sortedUsers);

            return $sortedUsers;
        } catch (Exception $e) {
            $this->logger->error("Error fetching users: " . $e->getMessage());
            throw new RuntimeException('Error fetching users');
        }
    }

    /**
     * @param int $userId User id
     * @return array User entry from the database
     * @throws Exception
     */
    public function getUserById(int $userId): array {
        try {
            $query = "SELECT * FROM $this->TABLE_NAME WHERE id = :userId";
            $params = [':userId' => [$userId, PDO::PARAM_INT]];
            $this->logger->info("Fetching user with ID: $userId.");
            $result = $this->executeStatement($query, $params);
            return $result[0];
        } catch (Exception $e) {
            $this->logger->error("Error fetching user with ID: $userId: " . $e->getMessage());
            throw new RuntimeException("Error fetching user with ID: $userId");
        }
    }

    /**
     * Fetches user by username.
     *
     * @param string $username
     * @return array User entry from the database
     * @throws Exception
     */
    function getUserByUsername(string $username): array
    {
        $this->logger->info("Fetching user with username: $username.");

        $query = "SELECT * FROM users WHERE username = :username";
        $params = [
            ':username' => [$username, PDO::PARAM_STR],
        ];

        try {
            $user = $this->executeStatement($query, $params);

            if (!$user) {
                $this->logger->error('No user found with this username.');
                return [];
            }

            $this->logger->debug('Fetched user data: ' . json_encode($user));
            return $user;
        } catch (Exception $e) {
            $this->logger->error('Error fetching user by username: ' . $e->getMessage());
            throw new RuntimeException('Error fetching user by username' . $e);
        }
    }

    /**
     * Adds a new user to the database.
     *
     * @param string $username
     * @param string $password
     * @return bool
     * @throws Exception
     */
    public function addUser(string $username, string $password): bool {
        try {
            $query = "INSERT INTO $this->TABLE_NAME (username, password, created_at) VALUES (:username, :password, :created_at)";
            $password = password_hash($password, PASSWORD_DEFAULT);
            $params = [
                ':username' => [$username, PDO::PARAM_STR],
                ':password' => [$password, PDO::PARAM_STR],
                ':created_at' => [date('Y-m-d'), PDO::PARAM_STR],
            ];
            $this->executeStatement($query, $params);
            $this->logger->info("Added new user.");
            return true;
        } catch (Exception $e) {
            $this->logger->error("Error adding new user: " . $e->getMessage());
            throw new RuntimeException('Error adding new user');
        }
    }

    /**
     * Updates a user in the database.
     *
     * @param int $userId
     * @param string $username
     * @param string $password
     * @return bool
     * @throws Exception
     */
    public function updateUser(int $userId, string $username, string $password): bool {
        try {
            $query = "UPDATE $this->TABLE_NAME SET username = :username, password = :password WHERE id = :userId";
            $params = [
                ':username' => [$username, PDO::PARAM_STR],
                ':password' => [$password, PDO::PARAM_STR],
                ':userId' => [$userId, PDO::PARAM_INT],
            ];
            $this->executeStatement($query, $params);
            $this->logger->info("User updated.");
            return true;
        } catch (PDOException $e) {
            $this->logger->error("Error updating user: " . $e->getMessage());
            throw new RuntimeException('Error updating user');
        }
    }

    /**
     * Deletes a user from the database.
     *
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function deleteUser(int $userId): bool {
        try {
            $query = "DELETE FROM $this->TABLE_NAME WHERE id = :userId";
            $params = [':userId' => [$userId, PDO::PARAM_INT]];
            $this->executeStatement($query, $params);
            $this->logger->info("User deleted.");
            return true;
        } catch (PDOException $e) {
            $this->logger->error("Error deleting user: " . $e->getMessage());
            throw new RuntimeException('Error deleting user');
        }
    }
}