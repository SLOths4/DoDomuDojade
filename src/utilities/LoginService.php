<?php

namespace src\utilities;

// All necessary imports
use Monolog\Logger;
use PDO;
use PDOException;

/**
 * Login page backend
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 * @version 0.0.0
 * @since 0.0.0
 */
class LoginService
{
    private PDO $pdo; // PDO instance
    private Logger $logger; // Monolog logger instance

    function __construct(Logger $loggerInstance, PDO $pdoInstance)
    {
        $this->logger = $loggerInstance;
        $this->pdo = $pdoInstance;
    }

    function authenticate($username, $password): bool
    {
        $this->logger->debug('Authentication started for username: ' . $username);
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);

        try {
            $this->logger->debug('Executing query to fetch user details');

            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $this->logger->debug('User found, verifying password');

                if (password_verify($password, $user['password'])) {
                    $this->logger->debug('Password verified successfully for username: ' . $username);
                    return true;
                } else {
                    $this->logger->debug('Password verification failed for username: ' . $username);
                }

            } else {
                $this->logger->debug('No user found with username: ' . $username);
            }
            return false;
        } catch (PDOException $e) {
            $this->logger->error('PDO error while checking credentials: ' . $e->getMessage());
            throw $e;
        }
    }
}