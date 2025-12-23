<?php

namespace App\Domain\Exception;

use App\Domain\Enum\ExceptionCodes;
use Throwable;

class DatabaseException extends DomainException
{
    private function __construct(string $message, int $code = 0, array $context = [], ?Throwable $previous = null) {
        parent::__construct($message, $code, $context, $previous);
    }

    public static function connectionFailed(string $dbDsn, ?Throwable $previous = null): DatabaseException {
        return new self(
            "Error while connecting to database",
            ExceptionCodes::DB_CONNECTION_FAILED->value,
            [
                'dbDsn' => $dbDsn,
            ],
            $previous
        );
    }

    public static function invalidCredentials(string $dbDsn, ?Throwable $previous = null): DatabaseException {
        return new self(
            "Invalid credentials",
            ExceptionCodes::DB_INVALID_CREDENTIALS->value,
            [
                'dbDsn' => $dbDsn,
            ],
            $previous
        );
    }

    public static function queryFailed(string $query, array $params = [], ?int $dbCode = null, ?Throwable $previous = null): DatabaseException
    {
        return new self(
            "Error while executing query",
            ExceptionCodes::DB_QUERY_FAILED->value,
            [
                'query' => $query,
                'params' => $params,
                'dbCode' => $dbCode,
            ],
            $previous
        );
    }
}