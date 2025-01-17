<?php
namespace src\utilities;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class TramService {
    private HttpClientInterface $httpClient;
    private string $ztm_url;
    private $config;


    public function __construct() {
        $this->httpClient = HttpClient::create();
        $this->config = require 'config.php';
        $this->ztm_url = $this->config['API'][1]['url'] ?? '';
    }

    /**
     * function fetches vechicle departures for porvided stop id
     *  @param $stopId string
     *  @return array
     *  @throws
     */
    private function getTimes($stopId):array {
        try {
            $data = $this->httpClient->request(
                'POST', $this->ztm_url,
                [
                    'headers' => array_merge(
                        [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        ]
                    ),"body" => array_merge(
                        [
                            "method" => "getTimes&p0={$stopId}"
                        ]
                    )
                ]
            );
            return $data;
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * function fetches vehicle departures for porvided stop id
     *  @param $lat float
     *  @param $lon float
     *  @return array
     *  @throws
     */
    private function getStops(float  $lat, float $lon):array {
        try {
            $data = $this->httpClient->request(
                'POST', $this->ztm_url,
                [
                    'headers' => array_merge(
                        [
                            'Content-Type' => 'application/x-www-form-urlencoded',
                        ]
                    ),
                    "body" => array_merge(
                        [
                            "method" => "getStops&p0={\"lat\":$lat,\"lon\":$lon}"
                        ]
                    )
                ]
            );
            return $data;
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    private function getLines($lineNumber):array {
        try {
            $data = $this->httpClient->request(
                'POST', $this->ztm_url,
                [
                    'headers' => array_merge(
                        [
                            'Content-Type' => 'application/x-www-form-urlencoded',
                        ]
                    ),
                    "body" => array_merge(
                        [
                            "method" => "getLines&p0={\"line\":$lineNumber}"
                        ]
                    )
                ]
            );
            return $data;
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    private function getRoutes($lineNumber):array {
        try {
            $data = $this->httpClient->request(
                'POST', $this->ztm_url,
                [
                    'headers' => array_merge(
                        [
                            'Content-Type' => 'application/x-www-form-urlencoded',
                        ]
                    ),
                    "body" => array_merge(
                        [
                            "method" => "getRoutes&p0={\"line\":$lineNumber}"
                        ]
                    )
                ]
            );
            return $data;
        }
        catch (\Exception $e) {
            throw $e;
        }
    }
}