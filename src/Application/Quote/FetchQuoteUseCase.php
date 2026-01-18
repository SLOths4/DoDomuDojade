<?php

namespace App\Application\Quote;

use App\Domain\Countdown\Event\CountdownCreatedEvent;
use App\Domain\Event\EventPublisher;
use App\Domain\Quote\Quote;
use App\Domain\Quote\QuoteCreatedEvent;
use App\Infrastructure\ExternalApi\Quote\QuoteApiService;
use App\Infrastructure\Persistence\PDOQuoteRepository;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

readonly class FetchQuoteUseCase
{
    public function __construct(
        private LoggerInterface    $logger,
        private QuoteApiService    $apiService,
        private PDOQuoteRepository $repository,
        private EventPublisher     $publisher,
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
        $id = $this->repository->add($quote);
        $this->publisher->publish(new QuoteCreatedEvent((string)$id, $quote->quote, $quote->author));
        $this->logger->info("Quote saved successfully");
    }
}