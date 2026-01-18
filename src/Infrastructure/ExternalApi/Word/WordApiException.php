<?php
namespace App\Infrastructure\ExternalApi\Word;

use App\Infrastructure\Shared\InfrastructureException;
use Throwable;

final class WordApiException extends InfrastructureException
{
    public static function fetchingFailed(Throwable $previous): self
    {
        return new self(
            'Failed to fetch word data from external API',
            'WORD_API_FETCH_FAILED',
            500,
            $previous
        );
    }

    public static function invalidResponse(): self
    {
        return new self(
            'Invalid response format from word API',
            'WORD_API_INVALID_RESPONSE',
            500
        );
    }
}
