<?php

namespace src\utilities;

use Exception;
use Monolog\Logger;
use RuntimeException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class used for fetching METAR data
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 * @version 1.0.0
 * @since 1.0.0
 */
class MetarService
{
    private HttpClientInterface $httpClient;
    private string $metar_url;
    private array $config; // TODO usunięcie configu
    private Logger $logger;

    public function __construct(Logger $loggerInstance)
    {
        $this->httpClient = HttpClient::create();
        $this->config = require '../config.php'; // TODO usunięcie configu
        $this->metar_url =  $this->getEnvVariable("METAR_URL")  ?? $this->config['Metar']['metar_url']; // TODO usunięcie configu
        $this->logger = $loggerInstance;
    }

    private function getEnvVariable(string $variableName): string {
        $value = getenv($variableName);
        if ($value === false) {
            throw new RuntimeException("Environment variable $variableName is not set.");
        }
        return $value;
    }

    /**
     * Function fetches metar data
     * @param string $AirportICAO ICAO code of airport e.g. EPPO
     * @return array
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Exception
     */
    public function getMetar(string $AirportICAO): array {
        if(empty($AirportICAO)) {
            $this->logger->error("AirportICAO is empty");
            throw new Exception("AirportICAO is empty");
        }
        try {
            $url = $this->metar_url . $AirportICAO;
            $response = $this->httpClient->request('GET', $url);

            return $response->toArray();
        } catch (Exception $e) {
            $this->logger->error('Error occurred while fetching METAR data: ' . $e->getMessage());
            throw new Exception("Error occurred while fetching METAR data: " . $e->getMessage());
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Transport Error occurred while fetching METAR data: ' . $e->getMessage());
            throw new TransportException("Transport Error occurred while fetching METAR data: " . $e->getMessage());
        }
    }
}