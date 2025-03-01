<?php

namespace src\models;

use Exception;
use Monolog\Logger;
use src\core\Model;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class used for fetching METAR data
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 */
class MetarModel extends Model
{
    private const array ENV_VARIABLES = ['METAR_URL'];
    private HttpClientInterface $httpClient;
    private Logger $logger;
    private string $metar_url;


    public function __construct(Logger $loggerInstance)
    {
        $this->httpClient = HttpClient::create();
        $this->metar_url =  $this->getEnvVariable("METAR_URL");
        $this->logger = $loggerInstance;
    }

    /**
     * Pobiera dane METAR dla danego kodu ICAO.
     *
     * @param string $airportIcaoCode
     *
     * @return array
     */
    public function getMetar(string $airportIcaoCode): array
    {
        if (!$this->isValidIcaoCode($airportIcaoCode)) {
            $this->logger->error("Invalid ICAO code provided.");
            return [];
        }


        try {
            $url = $this->metar_url . $airportIcaoCode;
            $xmlContent = $this->fetchData($url);
            return $this->extractMetarData($xmlContent);
        } catch (Exception $e) {
            $this->logger->error('Error occurred while fetching METAR data: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Ekstrahuje dane METAR z ciągu XML i konwertuje je do tablicy.
     *
     * @param string $xmlString Surowy ciąg XML pobrany z API
     *
     * @return array Zmapowane dane METAR
     */
    private function extractMetarData(string $xmlString): array
    {
        $xml = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($xml === false) {
            $this->logger->error("Nie udało się sparsować danych XML");
            return [];
        }

        $jsonEncodedData = json_encode($xml);
        $arrayData = json_decode($jsonEncodedData, true);

        $item = $arrayData['channel']['item'] ?? [];

        return [
            'title'       => $item['title'] ?? null,
            'link'        => $item['link'] ?? null,
            'description' => trim($item['description'] ?? ''),
        ];
    }

    /**
     * @param string $icaoCode
     * @return bool
     */
    private function isValidIcaoCode(string $icaoCode): bool
    {
        return !empty($icaoCode) && preg_match('/^[A-Z]{4}$/', $icaoCode);
    }

    /**
     * Wykonuje zapytanie GET pod dany URL i zwraca wynik jako tablicę.
     *
     * @param string $url
     * @return string
     */
    private function fetchData(string $url): string
    {
        try {
            $response = $this->httpClient->request('GET', $url);
            return $response->getContent();
        } catch (
        ClientExceptionInterface |
        RedirectionExceptionInterface |
        ServerExceptionInterface |
        TransportExceptionInterface $e
        ) {
            $this->logger->error("An error occurred while fetching data from $url: " . $e->getMessage());
        }

        return '';
    }

}