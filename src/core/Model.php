<?php
namespace src\core;

use Exception;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class Model extends CommonService{
    protected static ?PDO $pdo;

    /**
     * @throws Exception
     */
    public static function initDatabase(): PDO
    {
        if (!isset(self::$pdo)){
            try {
                $db_password = self::getEnvVariable('DB_PASSWORD');
                $db_username = self::getEnvVariable('DB_USERNAME');
                $db_host = self::getEnvVariable('DB_HOST');

                if (empty($db_host)) {
                    self::$logger->error('Database host cannot be empty.');
                }

                if (!empty($db_password) and !empty($db_username)) {
                    self::$pdo = new PDO($db_host, $db_username, $db_password);
                } else {
                    self::$pdo = new PDO($db_host);
                }
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (Exception $e) {
                self::$logger->error("Wystąpił błąd: " . $e->getMessage());
                throw new Exception("Błąd połączenia z bazą danych.");
            }

        }
        return self::$pdo;
    }

    /**
     * Waliduje strukturę pojedynczego parametru.
     *
     * @param mixed $param
     * @param mixed $key
     *
     * @return array
     * @throws Exception
     */
    private function validateParam($param, $key): array {
        if (!is_array($param)) {
            self::$logger->error("Invalid parameter structure: expected an array.", [
                'key' => $key,
                'param' => $param
            ]);
            throw new Exception("Invalid parameter structure: parameter is not an array");
        }

        if (count($param) !== 2) {
            // Jeśli drugi element nie istnieje, oznacza to brak typu parametru
            if (!array_key_exists(1, $param)) {
                self::$logger->error("Parameter type is missing.", [
                    'key' => $key,
                    'param' => $param
                ]);
                throw new Exception("Parameter type is missing");
            }
            self::$logger->error("Invalid parameter structure: expected exactly 2 elements.", [
                'key' => $key,
                'param' => $param
            ]);
            throw new Exception("Invalid parameter structure");
        }

        return $param;
    }

    /**
     * Binds parameters to a PDOStatement with validation and logging.
     *
     * @param PDOStatement $stmt
     * @param array $params
     *
     * @return void
     * @throws Exception
     */
    public function bindParams(PDOStatement $stmt, array $params): void {
        foreach ($params as $key => $param) {
            // Walidacja struktury parametru
            [$value, $type] = $this->validateParam($param, $key);

            self::$logger->debug("Binding parameter:", [
                'key'   => $key,
                'value' => $value,
                'type'  => $type
            ]);

            try {
                $stmt->bindValue($key, $value, $type ?? PDO::PARAM_STR);
            } catch (PDOException $e) {
                self::$logger->error("Failed to bind parameter to statement.", [
                    'key'   => $key,
                    'value' => $value,
                    'type'  => $type,
                    'error' => $e->getMessage(),
                ]);
                throw new Exception("Failed to bind parameter to statement.", 0, $e);
            }
        }
        self::$logger->debug("All parameters successfully bound.", ['parameters' => $params]);
    }


    /**
     * @param string $query
     * @param array $params
     *
     * @return array
     * @throws Exception
     */
    public function executeStatement(string $query, array $params = []): array {
        self::initDatabase();
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

}
