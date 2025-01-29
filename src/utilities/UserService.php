<?php

namespace src\utilities;

// All necessary imports
use InvalidArgumentException;
use Monolog\Logger;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Class used for operations on table storing users in provided database
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 * @version 1.0.0
 * @since 1.0.0
 */
class UserService{
    private PDO $pdo; // PDO instance
    private Logger $logger; // Monolog logger instance
    private string $table_name; // users table name

    public function __construct(Logger $loggerInstance, PDO $pdoInstance, string $table_name = 'users') {
        $this->logger = $loggerInstance;
        $this->pdo = $pdoInstance;
        $this->table_name = $table_name;

        $this->logger->debug("Users table name being used: $this->table_name");
    }

    /**
     *
     * @param PDOStatement $stmt
     * @param array $params
     * @return void
     */
    private function bindParams(PDOStatement $stmt, array $params): void {
        foreach ($params as $key => $param) {
            if (!is_array($param) || count($param) !== 2) {
                $this->logger->error("Invalid parameter structure.", [
                    'key' => $key,
                    'param' => $param
                ]);
                throw new InvalidArgumentException("Invalid parameter structure for key $key.");
            }

            [$value, $type] = $param;

            $this->logger->debug("Binding parameter:", [
                'key' => $key,
                'value' => $value,
                'type' => $type
            ]);

            try {
                $stmt->bindValue($key, $value, $type);
            } catch (PDOException $e) {
                $this->logger->error("Failed to bind parameter to statement.", [
                    'key' => $key,
                    'value' => $value,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
                throw new RuntimeException("Failed to bind parameter: $key");
            }
        }

        $this->logger->debug("All parameters successfully bound.", ['parameters' => $params]);
    }


    /**
     * Executes given statement
     * @param string $query Query to be executed
     * @param array $params Parameters
     * @return array
     */
    private function executeStatement(string $query, array $params = []): array {
        try {
            $stmt = $this->pdo->prepare($query);
            $this->logger->debug("Executing query:", ['query' => $query]);
            if (!empty($params)) {
                $this->bindParams($stmt, $params);
            }
            $start = microtime(true);
            $stmt->execute();
            $executionTime = round((microtime(true) - $start) * 1000, 2);
            $this->logger->info("SQL query executed successfully.", [
                'query' => $query,
                'execution_time_ms' => $executionTime,
                'parameters' => $params
            ]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($results)) {
                $this->logger->warning("SQL query executed but returned no results.", [
                    'query' => $query
                ]);
            }
            return $results;
        } catch (PDOException $e) {
            $this->logger->error("SQL query execution failed: " . $e->getMessage(), [
                'query' => $query,
                'parameters' => $params
            ]);
            throw new RuntimeException('Database operation failed');
        }
    }

    /**
     * @return array Array of users in the database
     */
    public function getUsers(): array {
        try {
            $query = "SELECT * FROM $this->table_name";
            $this->logger->info("Fetching all users.");
            return $this->executeStatement($query);
        } catch (PDOException $e) {
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
            $query = "SELECT * FROM $this->table_name WHERE id = :userId";
            $params = [':userId' => [$userId, PDO::PARAM_INT]];
            $this->logger->info("Fetching user with ID: $userId.");
            $result = $this->executeStatement($query, $params);
            return $result[0];
        } catch (PDOException $e) {
            $this->logger->error("Error fetching user with ID: $userId: " . $e->getMessage());
            throw new RuntimeException('Error fetching user with ID: $userId');
        }
    }

    /**
     * @param string $username
     * @return array User entry from the database
     */
    function getUserByUsername(string $username): array
    {
        $this->logger->info("Fetching user with username: $username.");

        // Przygotowanie zapytania
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
            return $user; // Zwróć dane użytkownika
        } catch (PDOException $e) {
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
            $query = "INSERT INTO $this->table_name (username, password, created_at) VALUES (:name, :password, :created_at)";
            $password = password_hash($password, PASSWORD_DEFAULT);
            $params = [
                ':name' => [$username, PDO::PARAM_STR],
                ':password' => [$password, PDO::PARAM_STR],
                ':created_at' => [date('Y-m-d'), PDO::PARAM_STR],
            ];
            $this->executeStatement($query, $params);
            $this->logger->info("Added new user.", []);
            return true;
        } catch (PDOException $e) {
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
            $query = "UPDATE $this->table_name SET name = :name, password = :password WHERE id = :userId";
            $params = [
                ':name' => [$username, PDO::PARAM_STR],
                ':password' => [$password, PDO::PARAM_STR],
                ':userId' => [$userId, PDO::PARAM_INT],
            ];
            $this->executeStatement($query, $params);
            $this->logger->info("User updated.", []);
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
            $query = "DELETE FROM $this->table_name WHERE id = :userId";
            $params = [':userId' => [$userId, PDO::PARAM_INT]];
            $this->executeStatement($query, $params);
            $this->logger->info("User deleted.", []);
            return true;
        } catch (PDOException $e) {
            $this->logger->error("Error deleting user: " . $e->getMessage());
            throw new RuntimeException('Error deleting user');
        }
    }
}