<?php
namespace App\Infrastructure\ExternalApi\Tram;

use App\Infrastructure\Shared\InfrastructureException;
use Throwable;


final class TramApiException extends InfrastructureException
{
    public static function transportError(Throwable $previous): self
    {
        return new self(
            'HTTP transport error occurred while calling ZTM API',
            'TRAM_TRANSPORT_ERROR',
            500,
            $previous
        );
    }

    public static function decodingError(Throwable $previous): self
    {
        return new self(
            'Unable to decode ZTM API response',
            'TRAM_DECODING_ERROR',
            500,
            $previous
        );
    }

    public static function clientError(Throwable $previous): self
    {
        return new self(
            'Client error occurred while calling ZTM API',
            'TRAM_CLIENT_ERROR',
            500,
            $previous
        );
    }

    public static function serverError(Throwable $previous): self
    {
        return new self(
            'Server error occurred while calling ZTM API',
            'TRAM_SERVER_ERROR',
            500,
            $previous
        );
    }

    public static function redirectionError(Throwable $previous): self
    {
        return new self(
            'Redirection error occurred while calling ZTM API',
            'TRAM_REDIRECTION_ERROR',
            500,
            $previous
        );
    }

    public static function invalidStopId(string $stopId): self
    {
        return new self(
            sprintf('Invalid stop ID format: %s', $stopId),
            'TRAM_INVALID_STOP_ID',
            500
        );
    }

    public static function invalidCoordinates(float $lat, float $lon): self
    {
        return new self(
            sprintf('Invalid GPS coordinates: lat=%f, lon=%f', $lat, $lon),
            'TRAM_INVALID_COORDINATES',
            500
        );
    }

    public static function invalidLineNumber(int $lineNumber): self
    {
        return new self(
            sprintf('Invalid line number: %d', $lineNumber),
            'TRAM_INVALID_LINE_NUMBER',
            500
        );
    }

    public static function noDepartureData(string $stopId): self
    {
        return new self(
            sprintf('No departure times available for stop: %s', $stopId),
            'TRAM_NO_DEPARTURE_DATA',
            500
        );
    }

    public static function noStopsData(float $lat, float $lon): self
    {
        return new self(
            sprintf('No stops found near coordinates: lat=%f, lon=%f', $lat, $lon),
            'TRAM_NO_STOPS_DATA',
            500
        );
    }

    public static function apiCallFailed(string $method, Throwable $previous): self
    {
        return new self(
            sprintf('ZTM API call failed for method: %s', $method),
            'TRAM_API_CALL_FAILED',
            500,
            $previous
        );
    }
}
