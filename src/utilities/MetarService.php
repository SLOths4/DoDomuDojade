<?php

namespace src\utilities;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class MetarService
{
    // Variables
    private HttpClientInterface $httpClient;
    private string $metar_url;
    private $config;


    public function __construct()
    {
        $this->httpClient = HttpClient::create();
        $this->config = require 'config.php';
        $this->metar_url = $this->config['Metar']['metar_url'];
    }


    /**
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMetar(): array {
        $metar_data = $this->httpClient->request('GET', $this->metar_url);

        return $metar_data->toArray();
    }


}