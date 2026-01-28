<?php

namespace App\Application\Quote;

use App\Domain\Quote\Quote;
use App\Infrastructure\ExternalApi\Quote\QuoteApiException;
use App\Infrastructure\ExternalApi\Quote\QuoteApiService;
use App\Infrastructure\Persistence\PDOQuoteRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

/**
 * Fetches quote from external API
 */
readonly class FetchQuoteUseCase
{
    /**
     * @param LoggerInterface $logger
     * @param QuoteApiService $apiService
     * @param PDOQuoteRepository $repository
     */
    public function __construct(
        private LoggerInterface    $logger,
        private QuoteApiService    $apiService,
        private PDOQuoteRepository $repository,
    ) {}

    /**
     * @return void
     * @throws QuoteApiException
     */
    public function execute(): void
    {
        if ($this->repository->fetchLatestQuote()) {
            $this->logger->warning("Quote has been already fetched today");
            return;
        }
        $this->logger->info("Starting daily quote fetch");
        $data = $this->apiService->getQuote();
        $quote = new Quote(
            null,
            $data['quote'],
            $data['author'],
            new DateTimeImmutable(),
        );
        $id = $this->repository->add($quote);
        $this->logger->info("Quote saved successfully");
    }
}