<?php
namespace src\core;

use Exception;
use InvalidArgumentException;
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
     * Validates and normalizes a parameter pair.
     * @param mixed $param
     * @param string|int $key
     * @return array{0: mixed, 1: int}
     * @throws InvalidArgumentException
     */
    private function validateParam(mixed $param, string|int $key): array
    {
        if (!is_array($param) || count($param) < 1) {
            $this->logger->error("Invalid parameter format", ['key' => $key, 'param' => $param]);
            throw new InvalidArgumentException("Parameter [$key] must be an array [value, type]");
        }

        $value = $param[0] ?? null;
        $type = $param[1] ?? PDO::PARAM_STR;

        if (!is_int($type)) {
            $this->logger->warning("Parameter type is not an int, defaulting to PDO::PARAM_STR", [
                'key' => $key, 'type' => $type
            ]);
            $type = PDO::PARAM_STR;
        }

        return [$value, $type];
    }

    /**
     * Binds parameters safely to a PDOStatement with validation and logging.
     * @param PDOStatement $stmt
     * @param array<string, array{0: mixed, 1?: int}> $params
     * @return void
     * @throws Exception
     */
    public function bindParams(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $param) {
            [$value, $type] = $this->validateParam($param, $key);

            try {
                $stmt->bindValue($key, $value, $type);
                $this->logger->debug("Bound parameter", [
                    'key' => $key,
                    'value' => $value,
                    'type' => $type
                ]);
            } catch (PDOException $e) {
                $this->logger->error("Failed to bind parameter", [
                    'key' => $key,
                    'value' => $value,
                    'type' => $type,
                    'error' => $e->getMessage()
                ]);
                throw new Exception("Failed to bind parameter [$key]", 0, $e);
            }
        }

        $this->logger->debug("All parameters successfully bound", ['parameters' => $params]);
    }

    /**
     * Executes given PDO statement.
     * @param string $query
     * @param array $params
     * @return PDOStatement
     * @throws RuntimeException|Exception
     */
    public function executeStatement(string $query, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $this->logger->debug("Executing query", ['query' => $query]);

            if (!empty($params)) {
                $this->bindParams($stmt, $params);
            }

            $start = microtime(true);
            $stmt->execute();
            $executionTime = round((microtime(true) - $start) * 1000, 2);

            $this->logger->debug("SQL query executed successfully", [
                'query' => $query,
                'execution_time_ms' => $executionTime,
                'parameters' => $params
            ]);

            return $stmt;

        } catch (PDOException $e) {
            $this->logger->error("SQL query execution failed: " . $e->getMessage(), [
                'query' => $query,
                'parameters' => $params
            ]);
            throw new RuntimeException('Database operation failed');
        }
    }

}