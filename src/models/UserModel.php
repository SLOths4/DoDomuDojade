<?php
namespace src\models;

use Exception;
use Monolog\Logger;
use PDO;
use PDOException;
use RuntimeException;
use src\core\Model;

/**
 * Class used for operations on table storing users in provided database
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 */
class UserModel extends Model
{
    private PDO $pdo;
    private Logger $logger;
    private string $TABLE_NAME;

    public function __construct() {
        $this->logger = self::initLogger();
        $this->pdo = self::initDatabase();
        $this->TABLE_NAME = self::getConfigVariable('USERS_TABLE_NAME') ?? 'users';

        $this->logger->debug("Users table name being used: $this->TABLE_NAME");
    }

    /**
     * @return array Array of users in the database
     */
    public function getUsers(): array {
        try {
            $query = "SELECT * FROM $this->TABLE_NAME";
            $this->logger->info("Fetching all users.");
            return $this->executeStatement($query);
        } catch (Exception $e) {
            $this->logger->error("Error fetching users: " . $e->getMessage());
            throw new RuntimeException('Error fetching users');
        }
    }

    /**
     * @param int $userId User id
     * @return array User entry from the database
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
            throw new RuntimeException('Error fetching user with ID: $userId');
        }
    }

    /**
     * @param string $username
     * @return array User entry from the database
     * @throws Exception
     */
    function getUserByUsername(string $username): array
    {
        $this->logger->info("Fetching user with username: $username.");

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);

        try {
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->logger->error('No user found with this username.');
                return [];
            }

            $this->logger->debug('Fetched user data: ' . json_encode($user));
            return $user;
        } catch (Exception $e) {
            $this->logger->error('Error fetching user by username: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @return bool Success
     */
    public function addUser(string $username, string $password): bool {
        try {
            $query = "INSERT INTO $this->TABLE_NAME (username, password, created_at) VALUES (:name, :password, :created_at)";
            $password = password_hash($password, PASSWORD_DEFAULT);
            $params = [
                ':name' => [$username, PDO::PARAM_STR],
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
     * @param int $userId
     * @param string $username
     * @param string $password
     * @return bool Success
     */
    public function updateUser(int $userId, string $username, string $password): bool {
        try {
            $query = "UPDATE $this->TABLE_NAME SET name = :name, password = :password WHERE id = :userId";
            $params = [
                ':name' => [$username, PDO::PARAM_STR],
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
     * @param int $userId
     * @return bool Success
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