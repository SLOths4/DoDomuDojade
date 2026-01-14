<?php

namespace App\Application\Quote;

use App\Domain\Quote\Quote;
use App\Infrastructure\Persistence\QuoteRepository;
use App\Infrastructure\Service\QuoteApiService;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

readonly class FetchQuoteUseCase
{
    public function __construct(
        private LoggerInterface $logger,
        private QuoteApiService $apiService,
        private QuoteRepository $repository
    ) {}

    /**
     * @throws Exception
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
        $this->repository->add($quote);
        $this->logger->info("Quote saved successfully");
    }
}