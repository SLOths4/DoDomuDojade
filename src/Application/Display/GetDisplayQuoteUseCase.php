<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Application\Quote\FetchActiveQuoteUseCase;
use Psr\Log\LoggerInterface;
use Exception;

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
     * @return array|null
     */
    public function execute(): ?array
    {
        try {
            $quote = $this->fetchActiveQuoteUseCase->execute();
        } catch (Exception $e) {
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
