<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Application\Quote\FetchActiveQuoteUseCase;
use App\Domain\Shared\DomainException;
use App\Infrastructure\Shared\InfrastructureException;
use Psr\Log\LoggerInterface;

/**
 * Provides quote data formatted for display page
 */
readonly class GetDisplayQuoteUseCase
{
    /**
     * @param FetchActiveQuoteUseCase $fetchActiveQuoteUseCase
     * @param LoggerInterface $logger
     */
    public function __construct(
        private FetchActiveQuoteUseCase $fetchActiveQuoteUseCase,
        private LoggerInterface $logger
    ) {}

    /**
     * @return array{from: string, quote: string}|null
     */
    public function execute(): ?array
    {
        try {
            $quote = $this->fetchActiveQuoteUseCase->execute();
        } catch (DomainException|InfrastructureException $e) {
            $this->logger->error("Failed to fetch quote", ['error' => $e->getMessage()]);
            return null;
        }

        if (!$quote) {
            return null;
        }

        return [
            'from' => $quote->author,
            'quote' => $quote->quote,
        ];
    }
}
