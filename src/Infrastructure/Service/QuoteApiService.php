<?php

namespace App\Infrastructure\Service;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fetches quote from an external provider
 */
readonly class QuoteApiService
{
    /**
     * @param LoggerInterface $logger
     * @param HttpClientInterface $httpClient
     * @param string $quoteApiUrl
     */
    public function __construct(
        private LoggerInterface              $logger,
        private HttpClientInterface          $httpClient,
        private string                       $quoteApiUrl
    ) {}

    /**
     * Fetches raw data from API
     * @throws Exception
     */
    private function fetchData(): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                $this->quoteApiUrl,
            );
            return $response->toArray();
        } catch (
        ClientExceptionInterface|
        RedirectionExceptionInterface|
        ServerExceptionInterface|
        TransportExceptionInterface|
        DecodingExceptionInterface $e
        ) {
            $this->logger->error("Error while fetching quotes data" . $e->getMessage());
            throw new Exception("Error while fetching quotes data" . $e->getMessage());
        } catch (Exception $e) {
            $this->logger->error("Unexpected error occurred while fetching quotes data " . $e->getMessage());
            throw new Exception("Unexpected error occurred while fetching quotes data " . $e->getMessage());
        }
    }

    /**
     * Formats data into app readable format
     * @throws Exception
     */
    public function getQuote(): array
    {
        $this->logger->debug("Starting fetching quote");

        $data = $this->fetchData();
        $this->logger->info("Quotes api response time is equal to: ". $data['responseTime']);

        $this->logger->info("Quote data successfully fetched");
        return [
            'quote' => $data['quote'],
            'author' => $data['from'],
        ];
    }
}