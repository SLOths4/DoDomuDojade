<?php

namespace src\utilities;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class ApiClient
{
    private  HttpClientInterface $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create();
    }

    /**
     * Make a GET request to the API
     *
     * @param string $url
     * @param array|null $query Optional query sent to the url
     * @param string|null $authToken Optional Authorization token
     * @return array
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     */
public function get(string $url, ?array $query = [], ?string $authToken = null): array
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'query' => $query,
                'headers' => array_merge([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                    $authToken ? ['Authorization' => 'Bearer ' . $authToken] : []),
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            throw new \RuntimeException('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Make a POST request to the API
     *
     * @param string $url
     * @param array $data
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function post(string $url, array $data = []): array
    {
        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => $data,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            throw new \RuntimeException('API request failed: ' . $e->getMessage());
        }
    }
}