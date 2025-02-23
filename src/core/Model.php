<?php
namespace src\core;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class Model {

    private static PDO $pdo;
    private static Logger $logger;
    public static function initDatabase(): PDO
    {
        if (!isset(self::$pdo)){
            try {
                $db_password = $_ENV['DB_PASSWORD'];
                $db_username = $_ENV['DB_USERNAME'];
                $db_host = $_ENV['DB_HOST'];

                if (!empty($db_password) and !empty($db_username)) {
                    self::$pdo = new PDO($db_host, $db_username, $db_password);
                } else {
                    self::$pdo = new PDO($db_host);
                }
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (Exception $e) {
                self::$logger->error("Wystąpił błąd: " . $e->getMessage());
            }

        }
        return self::$pdo;
    }

    public static function initLogger(): Logger
    {
        if (!isset(self::$logger)){
            try {
                self::$logger = new Logger('src');
                self::$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/src.logs', Level::Debug));
            } catch (Exception $e) {
                self::$logger->error("Wystąpił błąd: " . $e->getMessage());
            }
        }
        return self::$logger;
    }

    /**
     * @param string $variable
     * @return mixed|null
     */
    public function getConfigVariable(string $variable): mixed
    {
        try {
            $configPath = '../config/config.php';

            if (!file_exists($configPath)) {
                self::$logger->error("Plik konfiguracyjny nie istnieje: $configPath");
                return null;
            }

            $config = require $configPath;

            if (!is_array($config)) {
                self::$logger->error("Nieprawidłowy format pliku konfiguracyjnego.");
                return null;
            }

            if (!array_key_exists($variable, $config)) {
                self::$logger->warning("Zmienna konfiguracyjna '$variable' nie została znaleziona.");
                return null;
            }

            return $config[$variable];
        } catch (Exception $e) {
            self::$logger->error("Wystąpił błąd: " . $e->getMessage());
            return null;
        }
    }

    /**
     * @param PDOStatement $stmt
     * @param array $params
     *
     * @return void
     */
    public function bindParams(PDOStatement $stmt, array $params): void {
        foreach ($params as $key => $param) {
            if (!is_array($param) || count($param) !== 2) {
                self::$logger->error("Invalid parameter structure.", [
                    'key' => $key,
                    'param' => $param
                ]);
            }

            [$value, $type] = $param;

            self::$logger->debug("Binding parameter:", [
                'key' => $key,
                'value' => $value,
                'type' => $type
            ]);

            try {
                $stmt->bindValue($key, $value, $type);
            } catch (PDOException $e) {
                self::$logger->error("Failed to bind parameter to statement.", [
                    'key' => $key,
                    'value' => $value,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        self::$logger->debug("All parameters successfully bound.", ['parameters' => $params]);
    }

    /**
     * @param string $query
     * @param array $params
     *
     * @return array
     */
    public function executeStatement(string $query, array $params = []): array {
        try {
            $stmt = self::$pdo->prepare($query);
            self::$logger->debug("Executing query:", ['query' => $query]);
            if (!empty($params)) {
                $this->bindParams($stmt, $params);
            }
            $start = microtime(true);
            $stmt->execute();
            $executionTime = round((microtime(true) - $start) * 1000, 2);
            self::$logger->info("SQL query executed successfully.", [
                'query' => $query,
                'execution_time_ms' => $executionTime,
                'parameters' => $params
            ]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($results)) {
                self::$logger->warning("SQL query executed but returned no results.", [
                    'query' => $query
                ]);
            }
            return $results;
        } catch (PDOException $e) {
            self::$logger->error("SQL query execution failed: " . $e->getMessage(), [
                'query' => $query,
                'parameters' => $params
            ]);
            throw new RuntimeException('Database operation failed');
        }
    }

    /**
     * Pobiera zmienne z pliku .env
     *
     * @param string $variableName
     *
     * @return string|null
     */
    public function getEnvVariable(string $variableName): ?string {
        try {
            $value = $_ENV[$variableName];

            if ($value === false) {
                self::$logger->error("Environment variable $variableName is not set.");
            }

            return $value;
        } catch (Exception $e) {
            self::$logger->error("An error occurred: ". $e->getMessage());
            return null;
        }
    }

}
