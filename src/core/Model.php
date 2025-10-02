<?php
namespace src\core;

use Exception;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Psr\Log\LoggerInterface;

class Model{
    protected PDO $pdo;
    protected LoggerInterface $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger) {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * Validates parameter
     *
     * @param mixed $param
     * @param mixed $key
     * @return array
     * @throws Exception
     */
    private function validateParam(mixed $param, mixed $key): array {
        if (!is_array($param)) {
            $this->logger->error("Invalid parameter structure: expected an array.", [
                'key' => $key,
                'param' => $param
            ]);
            throw new Exception("Invalid parameter structure: parameter is not an array");
        }

        if (count($param) !== 2) {
            if (!array_key_exists(1, $param)) {
                $this->logger->error("Parameter type is missing.", [
                    'key' => $key,
                    'param' => $param
                ]);
                throw new Exception("Parameter type is missing");
            }
            $this->logger->error("Invalid parameter structure: expected exactly 2 elements.", [
                'key' => $key,
                'param' => $param
            ]);
            throw new Exception("Invalid parameter structure");
        }

        return $param;
    }

    /**
     * Binds parameters to a PDOStatement with validation and factories.
     *
     * @param PDOStatement $stmt
     * @param array $params
     * @return void
     * @throws Exception
     */
    public function bindParams(PDOStatement $stmt, array $params): void {
        foreach ($params as $key => $param) {
            [$value, $type] = $this->validateParam($param, $key);

            $this->logger->debug("Binding parameter:", [
                'key'   => $key,
                'value' => $value,
                'type'  => $type
            ]);

            try {
                $stmt->bindValue($key, $value, $type ?? PDO::PARAM_STR);
            } catch (PDOException $e) {
                $this->logger->error("Failed to bind parameter to statement.", [
                    'key'   => $key,
                    'value' => $value,
                    'type'  => $type,
                    'error' => $e->getMessage(),
                ]);
                throw new Exception("Failed to bind parameter to statement.", 0, $e);
            }
        }
        $this->logger->debug("All parameters successfully bound.", ['parameters' => $params]);
    }

    /**
     * Executes given PDO statement
     *
     * @param string $query
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function executeStatement(string $query, array $params = []): array {
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
}