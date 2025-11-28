<?php
declare(strict_types=1);

namespace App\Infrastructure\Exception;

enum ExceptionCodes: int
{
    case DB_INVALID_CREDENTIALS = 1001;
    case DB_CONNECTION_FAILED = 1002;
    case DB_QUERY_FAILED = 1003;

    // User exceptions (2000-2999)
    case USER_NOT_LOGGED_IN = 2001;
    case USER_NOT_FOUND = 2002;
    case USER_ALREADY_EXISTS = 2003;
    case USER_UNAUTHORIZED = 2004;

    // Validation exceptions (3000-3999)
    case VALIDATION_FAILED = 3001;
    case INVALID_INPUT = 3002;

    // WEB RELATED
    case INVALID_CSRF = 4001;
}