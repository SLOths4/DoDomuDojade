<?php

namespace App\Infrastructure\ExternalApi\Word;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

readonly class WordApiService
{
    public function __construct(
        private LoggerInterface $logger,
        private HttpClientInterface $httpClient,
        private string $wordApiUrl
    ) {}

    public static function todayIs(): string
    {
        $dateNow = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        return $dateNow->format('Y-m-d');
    }

    private function fetchData(): array
    {
        try {
            $this->logger->debug("Fetching word data from API", ['url' => $this->wordApiUrl]);

            $response = $this->httpClient->request(
                'GET',
                $this->wordApiUrl . self::todayIs(),
            );

            return $response->toArray();

        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface | DecodingExceptionInterface $e) {
            $this->logger->error("API error while fetching words data", ['error' => $e->getMessage()]);
            throw WordApiException::fetchingFailed($e);
        } catch (Throwable $e) {
            $this->logger->error("Unexpected error while fetching words data", ['error' => $e->getMessage()]);
            throw WordApiException::fetchingFailed($e);
        }
    }

    public function getWord(): array
    {
        $this->logger->debug("Starting fetching words");

        $data = $this->fetchData();

        // Validate response
        if (!isset($data['word'], $data['ipa'], $data['definition'])) {
            throw WordApiException::invalidResponse();
        }

        $this->logger->info("Word data successfully fetched");

        return [
            'word' => $data['word'],
            'ipa' => $data['ipa'],
            'definition' => $data['definition'],
        ];
    }
}
