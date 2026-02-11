<?php
namespace App\Infrastructure\ExternalApi\Calendar;

use App\Infrastructure\Shared\InfrastructureException;
use Throwable;

final class CalendarApiException extends InfrastructureException
{
    public static function clientInitializationFailed(Throwable $previous): self
    {
        return new self(
            'Failed to initialize Google Calendar client',
            'CALENDAR_CLIENT_INIT_FAILED',
            500,
            $previous
        );
    }

    public static function authenticationFailed(Throwable $previous): self
    {
        return new self(
            'Failed to authenticate with Google Calendar API',
            'CALENDAR_AUTH_FAILED',
            500,
            $previous
        );
    }

    public static function fetchingEventsFailed(Throwable $previous): self
    {
        return new self(
            'Failed to fetch events from Google Calendar',
            'CALENDAR_FETCH_FAILED',
            500,
            $previous
        );
    }

    public static function invalidApiKey(): self
    {
        return new self(
            'Invalid Google Calendar API key',
            'CALENDAR_INVALID_API_KEY',
            500
        );
    }
}
