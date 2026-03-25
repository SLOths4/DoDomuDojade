<?php

namespace App\Application\Quote;

use App\Domain\Quote\Quote;
use App\Domain\Quote\QuoteRepositoryInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Fetches active quote
 */
readonly class FetchActiveQuoteUseCase
{
    /**
     * @param LoggerInterface $logger
     * @param QuoteRepositoryInterface $repository
     */
    public function __construct(
        private LoggerInterface    $logger,
        private QuoteRepositoryInterface $repository,
    ) {}

    /**
     * Fetches today's quote
     * @return ?Quote
     * @throws Exception
     */
    public function execute(): ?Quote
    {
        $this->logger->info("Fetching active quote.");
        return $this->repository->fetchLatestQuote();
    }
}