<?php

namespace App\Infrastructure\ExternalApi\Quote;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

readonly class QuoteApiService
{
    public function __construct(
        private LoggerInterface $logger,
        private HttpClientInterface $httpClient,
        private string $quoteApiUrl
    ) {}

    private function fetchData(): array
    {
        try {
            $this->logger->debug("Fetching quote data from API", ['url' => $this->quoteApiUrl]);

            $response = $this->httpClient->request('GET', $this->quoteApiUrl);

            return $response->toArray();

        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface | DecodingExceptionInterface $e) {
            $this->logger->error("API error while fetching quotes data", ['error' => $e->getMessage()]);
            throw QuoteApiException::fetchingFailed($e);
        } catch (Throwable $e) {
            $this->logger->error("Unexpected error while fetching quotes data", ['error' => $e->getMessage()]);
            throw QuoteApiException::fetchingFailed($e);
        }
    }

    public function getQuote(): array
    {
        $this->logger->debug("Starting fetching quote");

        $data = $this->fetchData();

        // Validate response
        if (!isset($data['quote'], $data['from'])) {
            throw QuoteApiException::invalidResponse();
        }

        $this->logger->info("Quotes api response time", ['response_time' => $data['responseTime'] ?? 'N/A']);
        $this->logger->info("Quote data successfully fetched");

        return [
            'quote' => $data['quote'],
            'author' => $data['from'],
        ];
    }
}
