<?php
namespace App\Infrastructure\Database;

use App\Infrastructure\Shared\InfrastructureException;
use Throwable;

final class DatabaseException extends InfrastructureException
{
    public static function connectionFailed(string $dsn, Throwable $previous): self
    {
        return new self(
            'Failed to connect to database',
            'DB_CONNECTION_FAILED',
            500,
            $previous
        );
    }

    public static function invalidDsn(string $dsn): self
    {
        return new self(
            'Invalid database DSN',
            'DB_INVALID_DSN',
            500
        );
    }
    public static function executionFailed(string $query, Throwable $previous): self
    {
        return new self(
            'Database query execution failed',
            'DB_EXECUTION_ERROR',
            500,
            $previous
        );
    }

    public static function parameterBindingFailed(string $key, Throwable $previous): self
    {
        return new self(
            sprintf('Failed to bind parameter: %s', $key),
            'DB_BINDING_ERROR',
            500,
            $previous
        );
    }

    public static function invalidColumns(string $table, array $invalidColumns = []): self
    {
        return new self(
            sprintf('Invalid columns for table %s: %s', $table, implode(', ', $invalidColumns)),
            'DB_INVALID_COLUMNS',
            500
        );
    }

    public static function emptyUpdateData(): self
    {
        return new self(
            'Update data cannot be empty',
            'DB_EMPTY_UPDATE',
            500
        );
    }

    public static function emptyDeleteConditions(): self
    {
        return new self(
            'Delete requires conditions (safety measure)',
            'DB_EMPTY_DELETE_CONDITIONS',
            500
        );
    }

    public static function unsupportedDriver(string $driver): self
    {
        return new self(
            sprintf('Unsupported PDO driver: %s', $driver),
            'DB_UNSUPPORTED_DRIVER',
            500
        );
    }
}
