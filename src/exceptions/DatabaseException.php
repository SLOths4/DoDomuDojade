<?php

namespace src\exceptions;

use Throwable;

class DatabaseException extends BaseException
{
    private function __construct(string $message, int $code = 0, array $context = [], ?Throwable $previous = null) {
        parent::__construct($message, $code, $context, $previous);
    }

    public static function connectionFailed(string $dbDsn, ?Throwable $previous = null): DatabaseException {
        return new self(
            "Error while connecting to database",
            0,
            [
                'dbDsn' => $dbDsn,
            ],
            $previous
        );
    }

    public static function invalidCredentials(string $dbDsn, ?Throwable $previous = null): DatabaseException {
        return new self(
            "Invalid credentials",
            001,
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
            002,
            [
                'query' => $query,
                'params' => $params,
                'dbCode' => $dbCode,
            ],
            $previous
        );
    }
}