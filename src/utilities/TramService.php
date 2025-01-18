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
     * Pobiera czasy odjazdów dla danego przystanku.
     *
     * @param  string  $stopId  Symbol przystanku (unikalny identyfikator, np. RKAP71).
     * @return array            Tablica z czasami odjazdów i informacjami o liniach.
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    function getTimes(string $stopId): array {
        try {
            $response = $this->httpClient->request(
                'POST',
                $this->ztm_url,
                [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => [
                        'method' => 'getTimes',
                        'p0' => json_encode(['symbol' => $stopId]),
                    ],
                ]
            );

            // Zamiana odpowiedzi na tablicę
            $data = $response->toArray();

            // Sprawdzanie poprawności struktury odpowiedzi
            if (!isset($data['success']) || empty($data['success']['times'])) {
                throw new \Exception('Brak danych o odjazdach lub niekompletna odpowiedź API.');
            }

            return $data;//['success']['times']; // Zakładam, że czasy odjazdów są zwracane w kluczu 'times'
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Pobiera listę przystanków w okolicy wskazanej lokalizacji GPS.
     *
     * @param  float  $lat  Szerokość geograficzna (latitude).
     * @param  float  $lon  Długość geograficzna (longitude).
     * @return array         Lista przystanków w formacie tablicy asocjacyjnej.
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws /Exception
     */
    function getStops(float $lat, float $lon): array {
        try {
            $response = $this->httpClient->request(
                'POST',
                $this->ztm_url,
                [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => [
                        'method' => 'getStops',
                        'p0' => json_encode(['lat' => $lat, 'lon' => $lon]),
                    ],
                ]
            );

            // Jeśli potrzebujesz logiki walidacji odpowiedzi, np. sprawdzania, czy odpowiedź zawiera dane:
            $data = $response->toArray();

            if (!isset($data['success']) || empty($data['success'])) {
                throw new \Exception('Brak danych w odpowiedzi API lub niepoprawna struktura odpowiedzi.');
            }

            return $data['success']; // Zakładam, że to struktura zawiera dane przystanków zwróconych przez API.
        } catch (\Exception $e) {
            // Wyrzucenie błędu z pełną informacją lub logowanie dla debugowania.
            throw $e; // Możesz również zaimplementować specjalny handler błędów lub logowanie.
        }
    }

    function getLines(int $lineNumber):array {
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
            return $data->toArray();
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    function getRoutes(int $lineNumber):array {
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
            return $data->toArray();
        }
        catch (\Exception $e) {
            throw $e;
        }
    }
}