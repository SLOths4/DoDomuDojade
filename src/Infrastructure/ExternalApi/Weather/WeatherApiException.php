<?php
namespace App\Infrastructure\ExternalApi\Weather;

use App\Infrastructure\Shared\InfrastructureException;
use Throwable;

final class WeatherApiException extends InfrastructureException
{
    public static function imgwFetchingFailed(Throwable $previous): self
    {
        return new self(
            'Failed to fetch weather data from IMGW API',
            'WEATHER_IMGW_FETCH_FAILED',
            500,
            $previous
        );
    }

    public static function airlyFetchingFailed(Throwable $previous): self
    {
        return new self(
            'Failed to fetch air quality data from Airly API',
            'WEATHER_AIRLY_FETCH_FAILED',
            500,
            $previous
        );
    }

    public static function airlyEmptyData(): self
    {
        return new self(
            'Airly API returned empty data',
            'WEATHER_AIRLY_EMPTY_DATA',
            500
        );
    }

    public static function invalidApiKey(): self
    {
        return new self(
            'Invalid Airly API key',
            'WEATHER_INVALID_API_KEY',
            500
        );
    }
}
