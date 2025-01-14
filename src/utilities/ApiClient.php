<?php

namespace App\utilities;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class ApiClient
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create();
    }

    /**
     * Make a GET request to the API
     *
     * @param string $url
     * @param array $query
     * @return array
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function get(string $url, array $query = []): array
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
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
     * @param string $url
     * @param array $data
     * @return array
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
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