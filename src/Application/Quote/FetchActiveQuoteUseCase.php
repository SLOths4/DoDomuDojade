<?php

namespace App\Application\Quote;

use App\Domain\Quote\Quote;
use App\Infrastructure\Persistence\QuoteRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class FetchActiveQuoteUseCase
{
    public function __construct(
        private LoggerInterface $logger,
        private QuoteRepository $repository,
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