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
use DateTimeImmutable;
use DateTimeZone;

readonly class WordApiService
{
    public function __construct(
        private LoggerInterface              $logger,
        private HttpClientInterface          $httpClient,
        private string                       $wordApiUrl
    ) {}
    /**
     * @throws Exception
     */
    private function todayIs(): string
    {
        $dateNow = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        return $dateNow->format('d-m-Y');
    }
    /**
     * @throws Exception
     */
    private function fetchData(): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                $this->wordApiUrl . $this->todayIs(),
            );
            return $response->toArray();
        } catch (
        ClientExceptionInterface|
        RedirectionExceptionInterface|
        ServerExceptionInterface|
        TransportExceptionInterface|
        DecodingExceptionInterface $e
        ) {
            $this->logger->error("Error while fetching words data" . $e->getMessage());
            throw new Exception("Error while fetching words data" . $e->getMessage());
        } catch (Exception $e) {
            $this->logger->error("Unexpected error occurred while fetching words data " . $e->getMessage());
            throw new Exception("Unexpected error occurred while fetching words data " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getWord(): array
    {
        $this->logger->debug("Starting fetching words");

        $data = $this->fetchData();

        $this->logger->info("Word data successfully fetched");
        return [
            'word' => $data['word'],
            'ipa' => $data['ipa'],
            'definition' => $data['definition'],
        ];
    }
}