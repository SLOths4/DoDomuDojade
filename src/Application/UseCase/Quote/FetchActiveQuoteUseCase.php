<?php

namespace App\Application\UseCase\Quote;

use App\Domain\Entity\Quote;
use App\Infrastructure\Repository\QuoteRepository;
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