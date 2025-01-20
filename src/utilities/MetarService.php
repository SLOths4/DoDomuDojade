<?php

namespace src\utilities;

use Exception;
use Monolog\Logger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;

class MetarService
{
    private HttpClientInterface $httpClient;
    private string $metar_url;
    private Logger $logger;

    public function __construct(Logger $loggerInstance, string $metar_url)
    {
        $this->httpClient = HttpClient::create();
        $this->metar_url = $metar_url;
        $this->logger = $loggerInstance;
    }

    /**
     * Function fetches metar data
     * @return array | string
     */
    public function getMetar(): string | array
    {
        try {
            $response = $this->httpClient->request('GET', $this->metar_url);

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport error while fetching METAR data: ' . $e->getMessage());
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Client error (4xx) while fetching METAR data: ' . $e->getMessage());
        } catch (RedirectionExceptionInterface $e) {
            $this->logger->warning('Redirection error while fetching METAR data: ' . $e->getMessage());
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('Server error (5xx) while fetching METAR data: ' . $e->getMessage());
        } catch (DecodingExceptionInterface $e) {
            $this->logger->error('Decoding error while fetching METAR data: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->logger->critical('Unexpected error while fetching METAR data: ' . $e->getMessage());
        }

        return "No data available.";
    }
}