<?php

namespace App\Infrastructure\Helper;

use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Psr\Log\LoggerInterface;

class DatabaseHelper
{
    protected PDO $pdo;
    protected LoggerInterface $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * Validates and normalizes a parameter pair.
     *
     * Expects the parameter to be either a scalar value (which will be treated as [value, PDO::PARAM_STR])
     * or an array [value, type] where type is a PDO::PARAM_* constant.
     *
     * @param mixed $param The parameter value or [value, type] array.
     * @param string|int $key The parameter key for logging.
     * @return array{0: mixed, 1: int} Normalized [value, type].
     * @throws InvalidArgumentException If the format is invalid.
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
     *
     * Parameters should be in the format: [':key' => [value, type?]] or [':key' => value] for default string type.
     *
     * @param PDOStatement $stmt The prepared statement.
     * @param array<string, mixed> $params The parameters to bind.
     * @return void
     * @throws Exception If binding fails.
     */
    public function bindParams(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $param) {
            if (!is_array($param)) {
                $param = [$param];
            }
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
     *
     * @param string $query The SQL query to prepare and execute.
     * @param array<string, mixed> $params Parameters to bind (optional).
     * @return PDOStatement The executed statement.
     * @throws RuntimeException|Exception On preparation or execution failure.
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

    /**
     * Fetches a single row as an associative array.
     *
     * @param string $query The SQL query.
     * @param array<string, mixed> $params Parameters to bind (optional).
     * @return array|null The row or null if not found.
     * @throws RuntimeException|Exception On execution failure.
     */
    public function getOne(string $query, array $params = []): ?array
    {
        $stmt = $this->executeStatement($query, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->logger->debug("Fetched single row", ['query' => $query, 'found' => $result !== false]);

        return $result ?: null;
    }

    /**
     * Fetches all rows as an associative array.
     *
     * This method supports raw queries. For more structured queries, use buildSelectQuery() to generate the query string.
     *
     * @param string $query The SQL query.
     * @param array<string, mixed> $params Parameters to bind (optional).
     * @return array The rows.
     * @throws RuntimeException|Exception On execution failure.
     */
    public function getAll(string $query, array $params = []): array
    {
        $stmt = $this->executeStatement($query, $params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->logger->debug("Fetched multiple rows", ['query' => $query, 'count' => count($results)]);

        return $results;
    }

    /**
     * Fetches a single column value from the first row.
     *
     * @param string $query The SQL query.
     * @param array<string, mixed> $params Parameters to bind (optional).
     * @param int $columnIndex The column index (default 0).
     * @return mixed|null The value or null if not found.
     * @throws RuntimeException|Exception On execution failure.
     */
    public function getColumn(string $query, array $params = [], int $columnIndex = 0): mixed
    {
        $stmt = $this->executeStatement($query, $params);
        $result = $stmt->fetchColumn($columnIndex);

        $this->logger->debug("Fetched column value", ['query' => $query, 'column_index' => $columnIndex]);

        return $result !== false ? $result : null;
    }

    /**
     * Gets table column information, adapting to the PDO driver.
     *
     * Supports SQLite, MySQL/MariaDB, and PostgreSQL. Throws exception for unsupported drivers.
     *
     * @param string $table The table name.
     * @return array The column names.
     * @throws RuntimeException On failure or unsupported driver.
     */
    public function getTableColumns(string $table): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        try {
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("PRAGMA table_info($table)");
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
            } elseif ($driver === 'mysql') {
                $stmt = $this->pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :table");
                $stmt->bindValue(':table', $table, PDO::PARAM_STR);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($driver === 'pgsql') {
                $stmt = $this->pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = :table");
                $stmt->bindValue(':table', $table, PDO::PARAM_STR);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
            } else {
                throw new RuntimeException("Unsupported PDO driver: $driver");
            }
        } catch (PDOException $e) {
            $this->logger->error("Failed to fetch table columns", ['table' => $table, 'driver' => $driver, 'error' => $e->getMessage()]);
            throw new RuntimeException("Could not fetch table columns for: $table");
        }
    }

    /**
     * Validates columns before update/insert.
     *
     * @param string $table The table name.
     * @param array<string, mixed> $data The data keys to validate.
     * @return bool True if valid.
     * @throws InvalidArgumentException If invalid columns are found.
     * @throws RuntimeException On failure to fetch columns.
     */
    public function validateColumns(string $table, array $data): bool
    {
        $allowedColumns = $this->getTableColumns($table);
        $invalidColumns = array_diff(array_keys($data), $allowedColumns);

        if (!empty($invalidColumns)) {
            $this->logger->error("Invalid columns detected", [
                'table' => $table,
                'invalid_columns' => $invalidColumns,
                'allowed_columns' => $allowedColumns
            ]);
            throw new InvalidArgumentException(
                sprintf("Invalid columns for table '%s': %s", $table, implode(", ", $invalidColumns))
            );
        }

        return true;
    }

    /**
     * Inserts a single row and returns the last insert ID.
     *
     * Validation is enabled by default to check columns against the table schema.
     * Disable only for trusted, pre-validated data to improve performance.
     *
     * Data format: ['column' => value] or ['column' => [value, type]].
     *
     * @param string $table The table name.
     * @param array<string, mixed> $data The data to insert.
     * @param bool $validate Whether to validate columns (default true).
     * @return string|int The last insert ID.
     * @throws RuntimeException|Exception|InvalidArgumentException On failure.
     */
    public function insert(string $table, array $data, bool $validate = true): string|int
    {
        if ($validate) {
            $this->validateColumns($table, $data);
        }

        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(", ", $columns),
            implode(", ", $placeholders)
        );

        $params = [];
        foreach ($data as $key => $value) {
            $params[":$key"] = is_array($value) ? $value : [$value];
        }

        $this->executeStatement($query, $params);
        $lastId = $this->pdo->lastInsertId();

        $this->logger->info("Row inserted", ['table' => $table, 'last_id' => $lastId]);

        return $lastId;
    }

    /**
     * Inserts multiple rows in a single query for efficiency.
     *
     * All rows must have the same columns. Validation checks the first row's columns.
     * Data format: [['column' => value, ...], ...] or with types as [value, type].
     *
     * @param string $table The table name.
     * @param array<array<string, mixed>> $rows The rows to insert.
     * @param bool $validate Whether to validate columns (default true).
     * @return int The number of inserted rows.
     * @throws RuntimeException|Exception|InvalidArgumentException On failure or mismatched columns.
     */
    public function bulkInsert(string $table, array $rows, bool $validate = true): int
    {
        if (empty($rows)) {
            return 0;
        }

        $firstRow = $rows[0];
        if ($validate) {
            $this->validateColumns($table, $firstRow);
        }

        $columns = array_keys($firstRow);
        $valuesClauses = [];
        $params = [];

        foreach ($rows as $rowIndex => $row) {
            if (array_keys($row) !== $columns) {
                throw new InvalidArgumentException("All rows must have the same columns");
            }

            $placeholders = [];
            foreach ($columns as $col) {
                $paramKey = ":{$col}_{$rowIndex}";
                $placeholders[] = $paramKey;
                $params[$paramKey] = is_array($row[$col]) ? $row[$col] : [$row[$col]];
            }
            $valuesClauses[] = '(' . implode(', ', $placeholders) . ')';
        }

        $query = sprintf(
            "INSERT INTO %s (%s) VALUES %s",
            $table,
            implode(", ", $columns),
            implode(", ", $valuesClauses)
        );

        $stmt = $this->executeStatement($query, $params);
        $rowCount = $stmt->rowCount();

        $this->logger->info("Bulk rows inserted", ['table' => $table, 'rows_inserted' => $rowCount]);

        return $rowCount;
    }

    /**
     * Updates rows in a table.
     *
     * Validation is enabled by default. Disable only for trusted data.
     * If conditions are empty, all rows will be updated—use with caution.
     *
     * Data/conditions format: ['column' => value] or ['column' => [value, type]].
     *
     * @param string $table The table name.
     * @param array<string, mixed> $data The data to update.
     * @param array<string, mixed> $conditions The WHERE conditions (optional).
     * @param bool $validate Whether to validate columns (default true).
     * @return int The number of affected rows.
     * @throws RuntimeException|Exception|InvalidArgumentException On failure.
     */
    public function update(string $table, array $data, array $conditions = [], bool $validate = true): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Update data cannot be empty");
        }

        if ($validate) {
            $this->validateColumns($table, $data);
        }

        $setClause = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setClause[] = "$key = :set_$key";
            $params[":set_$key"] = is_array($value) ? $value : [$value];
        }

        $query = "UPDATE $table SET " . implode(", ", $setClause);

        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                $whereClause[] = "$key = :where_$key";
                $params[":where_$key"] = is_array($value) ? $value : [$value];
            }
            $query .= " WHERE " . implode(" AND ", $whereClause);
        } else {
            $this->logger->warning("Updating all rows in table without conditions", ['table' => $table]);
        }

        $stmt = $this->executeStatement($query, $params);
        $rowCount = $stmt->rowCount();

        $this->logger->info("Rows updated", ['table' => $table, 'rows_affected' => $rowCount]);

        return $rowCount;
    }

    /**
     * Deletes rows from a table.
     *
     * Conditions are required to prevent accidental full-table deletion.
     *
     * @param string $table The table name.
     * @param array<string, mixed> $conditions The WHERE conditions.
     * @return int The number of affected rows.
     * @throws RuntimeException|Exception|InvalidArgumentException On failure or empty conditions.
     */
    public function delete(string $table, array $conditions = []): int
    {
        if (empty($conditions)) {
            throw new InvalidArgumentException("Delete conditions cannot be empty (safety measure)");
        }

        $whereClause = [];
        $params = [];

        foreach ($conditions as $key => $value) {
            $whereClause[] = "$key = :$key";
            $params[":$key"] = is_array($value) ? $value : [$value];
        }

        $query = "DELETE FROM $table WHERE " . implode(" AND ", $whereClause);

        $stmt = $this->executeStatement($query, $params);
        $rowCount = $stmt->rowCount();

        $this->logger->info("Rows deleted", ['table' => $table, 'rows_affected' => $rowCount]);

        return $rowCount;
    }

    /**
     * Counts rows in a table with optional conditions.
     *
     * @param string $table The table name.
     * @param array<string, mixed> $conditions The WHERE conditions (optional).
     * @return int The count.
     * @throws RuntimeException|Exception On failure.
     */
    public function count(string $table, array $conditions = []): int
    {
        $query = "SELECT COUNT(*) FROM $table";
        $params = [];

        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                $whereClause[] = "$key = :$key";
                $params[":$key"] = is_array($value) ? $value : [$value];
            }
            $query .= " WHERE " . implode(" AND ", $whereClause);
        }

        $count = $this->getColumn($query, $params);
        return (int)$count;
    }

    /**
     * Checks if a row exists.
     *
     * @param string $table The table name.
     * @param array<string, mixed> $conditions The WHERE conditions (optional).
     * @return bool True if exists.
     * @throws RuntimeException|Exception On failure.
     */
    public function exists(string $table, array $conditions = []): bool
    {
        return $this->count($table, $conditions) > 0;
    }

    /**
     * Builds a simple SELECT query with optional conditions, limit, and offset.
     *
     * This is a lightweight query builder for common SELECT operations.
     * For more complex queries, use raw SQL with executeStatement or getAll.
     *
     * @param string $table The table name.
     * @param array<string, mixed> $conditions WHERE conditions (optional).
     * @param int|null $limit LIMIT clause (optional).
     * @param int|null $offset OFFSET clause (optional).
     * @param string $columns Columns to select (default '*').
     * @param string|null $orderBy ORDER BY clause (optional, e.g., 'id DESC').
     * @return array{query: string, params: array<string, mixed>} The query and params.
     */
    public function buildSelectQuery(
        string $table,
        array $conditions = [],
        ?int $limit = null,
        ?int $offset = null,
        string $columns = '*',
        ?string $orderBy = null
    ): array {
        $query = "SELECT $columns FROM $table";
        $params = [];

        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                $whereClause[] = "$key = :$key";
                $params[":$key"] = is_array($value) ? $value : [$value];
            }
            $query .= " WHERE " . implode(" AND ", $whereClause);
        }

        if ($orderBy) {
            $query .= " ORDER BY $orderBy";
        }

        if ($limit !== null) {
            $query .= " LIMIT :limit";
            $params[':limit'] = [$limit, PDO::PARAM_INT];
            if ($offset !== null) {
                $query .= " OFFSET :offset";
                $params[':offset'] = [$offset, PDO::PARAM_INT];
            }
        }

        return ['query' => $query, 'params' => $params];
    }

    /**
     * Begins a database transaction.
     *
     * @return bool True on success.
     */
    public function beginTransaction(): bool
    {
        $result = $this->pdo->beginTransaction();
        $this->logger->debug("Transaction started");
        return $result;
    }

    /**
     * Commits a database transaction.
     *
     * @return bool True on success.
     */
    public function commit(): bool
    {
        $result = $this->pdo->commit();
        $this->logger->debug("Transaction committed");
        return $result;
    }

    /**
     * Rolls back a database transaction.
     *
     * @return bool True on success.
     */
    public function rollback(): bool
    {
        $result = $this->pdo->rollBack();
        $this->logger->warning("Transaction rolled back");
        return $result;
    }
}