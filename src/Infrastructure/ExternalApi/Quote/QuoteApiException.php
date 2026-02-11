<?php
namespace App\Infrastructure\ExternalApi\Quote;

use App\Infrastructure\Shared\InfrastructureException;
use Throwable;

final class QuoteApiException extends InfrastructureException
{
    public static function fetchingFailed(Throwable $previous): self
    {
        return new self(
            'Failed to fetch quote data from external API',
            'QUOTE_API_FETCH_FAILED',
            500,
            $previous
        );
    }

    public static function invalidResponse(): self
    {
        return new self(
            'Invalid response format from quote API',
            'QUOTE_API_INVALID_RESPONSE',
            500
        );
    }
}
