<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class ApiClient
{
    private $httpClient;
    private $baseUrl;

    public function __construct(string $baseUrl = 'https://api.example.com')
    {
        $this->httpClient = HttpClient::create();
        $this->baseUrl = $baseUrl;
    }

    /**
     * Make a GET request to the API
     *
     * @param string $endpoint
     * @param array $query
     * @return array
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function get(string $endpoint, array $query = []): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . $endpoint, [
                'query' => $query,
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

    /**
     * Make a POST request to the API
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function post(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->baseUrl . $endpoint, [
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

    /**
     * Set authorization token for requests
     *
     * @param string $token
     * @return void
     */
    public function setAuthToken(string $token): void
    {
        $this->httpClient = HttpClient::create([
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
    }
}