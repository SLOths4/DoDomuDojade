<?php

namespace App\Infrastructure\Service;

use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class WeatherService {
    public function __construct(
        private LoggerInterface     $logger,
        private HttpClientInterface $httpClient,
        private string              $imgwWeatherUrl,
        private string              $airlyUrl,
        private string              $airlyApiKey,
        private string              $airlyLocationId,
    ){}

    /**
     * Makes HTTP request to the specified URL.
     * @param string $url
     * @param array $headers
     * @return array
     * @throws Exception
     */
    private function fetchData(string $url, array $headers = []): array
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers,
            ]);
            return $response->toArray();
        } catch (
        ClientExceptionInterface|
        RedirectionExceptionInterface|
        ServerExceptionInterface|
        TransportExceptionInterface|
        DecodingExceptionInterface $e
        ) {
            $this->logger->error("Error while fetching data from " . $url . " " . $e->getMessage());
            throw new Exception("Error while fetching data from " . $url . " " . $e->getMessage());
        } catch (Exception $e) {
            $this->logger->error("Unexpected error occurred while fetching data from " . $url . " " . $e->getMessage());
            throw new Exception("Unexpected error occurred while fetching data from " . $url . " " . $e->getMessage());
        }
    }

    /**
     * Maps the raw data from the API to a more usable format.
     * @param array $data Raw data from the API.
     * @return array Mapped data.
     */
    private function extractAirlyData(array $data): array
    {
        $current = $data['current'] ?? [];
        $values = $current['values'] ?? [];
        $index = $current['indexes'][0] ?? [];

        return [
            'fromDateTime' => $current['fromDateTime'] ?? null,
            'tillDateTime' => $current['tillDateTime'] ?? null,
            'pm_1_value' => $values[0]['value'] ?? null,
            'pm_25_value' => $values[1]['value'] ?? null,
            'pm_10_value' => $values[3]['value'] ?? null,
            'pressure_value' => $values[4]['value'] ?? null,
            'humidity_value' => $values[5]['value'] ?? null,
            'temperature_value' => $values[6]['value'] ?? null,
            'airly_index_value' => $index['value'] ?? null,
            'airly_index_level' => $index['level'] ?? null,
            'airly_index_colour' => $index['color'] ?? null,
            'airly_index_name' => $index['name'] ?? null,
            'airly_index_description' => $index['description'] ?? null,
            'airly_index_advice' => $index['advice'] ?? null,
        ];
    }

    /**
     * Fetches data from Airly API and extracts relevant information.
     * @return array
     * @throws Exception
     */
    private function getAirlyData(): array
    {
        $this->logger->info("Rozpoczęto pobieranie danych z API Airly", ['url' => $this->airlyUrl]);

        $url = $this->airlyUrl . '?locationId=' . urlencode($this->airlyLocationId);

        $headers = [
            'Accept' => 'Application/json',
            'Content-Type' => 'Application/json',
            'apikey' => $this->airlyApiKey,
            'Accept-Language' => 'pl',
        ];

        try {
            $data = $this->fetchData($url, $headers);
            $this->logger->info("Pomyślnie pobrano dane z API Airly");
        } catch (RuntimeException $e) {
            $this->logger->warning("Nie udało się pobrać danych z API Airly: " . $e->getMessage());
            return $this->extractAirlyData([]);
        }

        if (empty($data)) {
            $this->logger->error("Dane z Airly są puste. Możliwa awaria API.");
            throw new Exception("API Airly returned empty data. Possible API failure.");
        }

        return $this->extractAirlyData($data);
    }

    /**
     * Fetches data from IMGW API and extracts relevant information.
     * @return array
     * @throws RuntimeException
     */
    private function getImgwWeatherData(): array
    {
        $this->logger->debug("Rozpoczęto pobieranie danych z API IMGW");

        $headers = [
            'Accept' => 'Application/json',
            'Content-Type' => 'Application/json',
        ];

        try {
            $data = $this->fetchData($this->imgwWeatherUrl, $headers);
            $this->logger->debug("Pomyślnie pobrano dane z API IMGW");
        } catch (Exception $e) {
            $this->logger->error("Błąd podczas pobierania danych z API IMGW: " . $e->getMessage());
            throw new RuntimeException("Error while fetching IMGW weather data: " . $e->getMessage());
        }

        return $data;
    }

    /**
     * Merges data from IMGW and Airly APIs and returns a single array.
     * @return array
     * @throws Exception
     */
    public function getWeather(): array
    {
        $this->logger->info('Fetching combined weather data');

        $imgwData = $this->getImgwWeatherData();
        $airlyData = $this->getAirlyData();

        $result = [
            'imgw_station' => $imgwData['stacja'] ?? null,
            'imgw_fromDate' => $imgwData['data_pomiaru'] ?? null,
            'imgw_fromHour' => $imgwData['godzina_pomiaru'] ?? null,
            'imgw_temperature' => $imgwData['temperatura'] ?? null,
            'imgw_wind_speed' => $imgwData['predkosc_wiatru'] ?? null,
            'imgw_wind_direction' => $imgwData['kierunek_wiatru'] ?? null,
            'imgw_humidity' => $imgwData['wilgotnosc_wzgledna'] ?? null,
            'imgw_precipitation_sum' => $imgwData['suma_opadu'] ?? null,
            'imgw_pressure' => $imgwData['cisnienie'] ?? null,
            'airly_fromDateTime' => $airlyData['fromDateTime'] ?? null,
            'airly_tillDateTime' => $airlyData['tillDateTime'] ?? null,
            'airly_index_value' => $airlyData['airly_index_value'] ?? null,
            'airly_index_advice' => $airlyData['airly_index_advice'] ?? null,
            'airly_index_colour' => $airlyData['airly_index_colour'] ?? null,
            'airly_index_description' => $airlyData['airly_index_description'] ?? null,
        ];

        $this->logger->info('Combined weather data prepared', [
            'has_imgw' => $result['imgw_station'] !== null,
            'has_airly' => $result['airly_index_value'] !== null,
        ]);

        return $result;
    }
}