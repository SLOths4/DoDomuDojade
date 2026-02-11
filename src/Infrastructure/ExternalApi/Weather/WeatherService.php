<?php

namespace App\Infrastructure\ExternalApi\Weather;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * Used to interact with IMGW API and airly API
 */
readonly class WeatherService
{
    public function __construct(
        private LoggerInterface $logger,
        private HttpClientInterface $httpClient,
        private string $imgwWeatherUrl,
        private string $airlyUrl,
        private string $airlyApiKey,
        private string $airlyLocationId,
    ) {}

    private function fetchData(string $url, array $headers = []): array
    {
        try {
            $this->logger->debug("Fetching data from API", ['url' => $url]);

            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers,
            ]);

            return $response->toArray();

        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface | DecodingExceptionInterface $e) {
            $this->logger->error("API error while fetching data", ['url' => $url, 'error' => $e->getMessage()]);
            throw WeatherApiException::imgwFetchingFailed($e);
        } catch (Throwable $e) {
            $this->logger->error("Unexpected error while fetching data", ['url' => $url, 'error' => $e->getMessage()]);
            throw WeatherApiException::imgwFetchingFailed($e);
        }
    }

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

    private function getAirlyData(): array
    {
        try {
            if (empty($this->airlyApiKey)) {
                throw WeatherApiException::invalidApiKey();
            }

            $url = $this->airlyUrl . '?locationId=' . urlencode($this->airlyLocationId);

            $this->logger->info("Fetching air quality data from Airly", ['url' => $this->airlyUrl]);

            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'apikey' => $this->airlyApiKey,
                'Accept-Language' => 'pl',
            ];

            $data = $this->fetchData($url, $headers);

            if (empty($data)) {
                $this->logger->error("Airly API returned empty data");
                throw WeatherApiException::airlyEmptyData();
            }

            $this->logger->info("Air quality data successfully fetched from Airly");
            return $this->extractAirlyData($data);

        } catch (Throwable $e) {
            $this->logger->warning("Failed to fetch Airly data, returning empty defaults", ['error' => $e->getMessage()]);
            return $this->extractAirlyData([]);
        }
    }

    private function getImgwWeatherData(): array
    {
        try {
            $this->logger->debug("Fetching weather data from IMGW");

            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            $data = $this->fetchData($this->imgwWeatherUrl, $headers);

            $this->logger->info("Weather data successfully fetched from IMGW");
            return $data;

        } catch (Throwable $e) {
            $this->logger->error("Failed to fetch IMGW weather data", ['error' => $e->getMessage()]);
            throw WeatherApiException::imgwFetchingFailed($e);
        }
    }

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

        $this->logger->info('Combined weather data prepared');

        return $result;
    }
}
