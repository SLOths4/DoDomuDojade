<?php
namespace src\utilities;

use Exception;
use Monolog\Logger;
use RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class WeatherService {
    private const array ENV_VARIABLES = ['IMGW_WEATHER_URL', 'AIRLY_ENDPOINT', 'AIRLY_API_KEY', 'AIRLY_LOCATION_ID', 'AIRLY_URL'];
    private  HttpClientInterface $httpClient;
    private string $imgwWeatherUrl;
    private string $airQualityUrl;
    private string $airlyApiKey;
    private string $airlyLocationId;
    private string $airlyUrl;
    private Logger $logger;


    public function __construct(Logger $logger) {
        $this->httpClient = HttpClient::create();
        $this->logger = $logger;

        $this->imgwWeatherUrl = $this->getEnvVariable('IMGW_WEATHER_URL');
        $this->airlyUrl = $this->getEnvVariable('AIRLY_ENDPOINT');
        $this->airlyApiKey = $this->getEnvVariable('AIRLY_API_KEY');
        $this->airlyLocationId = ltrim($this->getEnvVariable('AIRLY_LOCATION_ID'), '/');
        $this->airQualityUrl = $this->airlyUrl . $this->airlyLocationId;
    }

    /**
     * Pobiera zmienne z pliku .env
     *
     * @param string $variableName
     *
     * @return string
     */
    private function getEnvVariable(string $variableName): string {
        $value = getenv($variableName);
        if ($value === false) {
            $this->logger->error("Environment variable $variableName is not set. Expected variables: " . implode(',', self::ENV_VARIABLES));
        }
        return $value;
    }


    /**
     * Wykonuje zapytanie HTTP do podanego URL z opcjonalnymi nagłówkami.
     *
     * @param string $url
     * @param array  $headers
     *
     * @return array
     */
    private function fetchData(string $url, array $headers = []): array
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers,
            ]);
            return $response->toArray();
        } catch (
        ClientExceptionInterface |
        RedirectionExceptionInterface |
        ServerExceptionInterface |
        TransportExceptionInterface |
        DecodingExceptionInterface $e
        ) {
            $this->logger->error(sprintf("Błąd podczas pobierania danych z %s: %s", $url, $e->getMessage()));
        } catch (Exception $e) {
            $this->logger->error(sprintf("Nieoczekiwany błąd podczas pobierania danych z %s: %s", $url, $e->getMessage()));
        }
        return [];
    }

    /**
     * Ekstrahuje i mapuje dane jakości powietrza z surowej odpowiedzi API.
     *
     * @param array $data Surowe dane zwrócone przez API
     *
     * @return array Zmapowane dane jakości powietrza
     */
    private function extractAirlyData(array $data): array
    {
        $current = $data['current'] ?? [];
        $values  = $current['values'] ?? [];
        $index   = $current['indexes'][0] ?? [];

        return [
            'fromDateTime'            => $current['fromDateTime'] ?? null,
            'tillDateTime'            => $current['tillDateTime'] ?? null,
            'pm_1_value'              => $values[0]['value'] ?? null,
            'pm_25_value'             => $values[1]['value'] ?? null,
            'pm_10_value'             => $values[3]['value'] ?? null,
            'pressure_value'          => $values[4]['value'] ?? null,
            'humidity_value'          => $values[5]['value'] ?? null,
            'temperature_value'       => $values[6]['value'] ?? null,
            'airly_index_value'       => $index['value'] ?? null,
            'airly_index_level'       => $index['level'] ?? null,
            'airly_index_colour'      => $index['color'] ?? null,
            'airly_index_name'        => $index['name'] ?? null,
            'airly_index_description' => $index['description'] ?? null,
            'airly_index_advice'      => $index['advice'] ?? null,
        ];
    }

    /**
     * Pobiera dane jakości powietrza z API Airly i mapuje je przy pomocy extractAirQualityData().
     *
     * @return array
     */
    private function getAirlyData(): array
    {
        $this->logger->info("Rozpoczęto pobieranie danych z API Airly", ['url' => $this->airQualityUrl]);

        $headers = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'apikey'       => $this->airlyApiKey,
        ];

        try {
            $data = $this->fetchData($this->airQualityUrl, $headers);
            $this->logger->info("Pomyślnie pobrano dane z API Airly");
        } catch (RuntimeException $e) {
            $this->logger->warning("Nie udało się pobrać danych z API Airly: " . $e->getMessage());
            return $this->extractAirlyData([]); // Zwracamy dane z wartościami null
        }

        if (empty($data)) {
            $this->logger->error("Dane z Airly są puste. Możliwa awaria API.");
            return $this->extractAirlyData([]);
        }

        return $this->extractAirlyData($data);
    }

    /**
     * Pobiera dane pogodowe z API IMGW.
     *
     * @return array
     *
     * @throws RuntimeException
     */
    private function getImgwWeatherData(): array
    {
        $this->logger->info("Rozpoczęto pobieranie danych z API IMGW");

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        try {
            $data = $this->fetchData($this->imgwWeatherUrl, $headers);
            $this->logger->info("Pomyślnie pobrano dane z API IMGW");
        } catch (Exception $e) {
            $this->logger->error("Błąd podczas pobierania danych z API IMGW: " . $e->getMessage());
            throw new RuntimeException("Błąd podczas pobierania danych pogodowych IMGW: " . $e->getMessage());
        }

        return $data;
    }

    /**
     * Łączy dane pogodowe z IMGW oraz dane jakości powietrza z Airly.
     *
     * @return array
     *
     * @noinspection SpellCheckingInspection
     */
    public function getWeather(): array
    {
        $imgwData = $this->getImgwWeatherData();
        $airlyData = $this->getAirlyData();

        return [
            'imgw_station'           => $imgwData['stacja'] ?? null,
            'imgw_fromDate'          => $imgwData['data_pomiaru'] ?? null,
            'imgw_fromHour'          => $imgwData['godzina_pomiaru'] ?? null,
            'imgw_temperature'       => $imgwData['temperatura'] ?? null,
            'imgw_wind_speed'        => $imgwData['predkosc_wiatru'] ?? null,
            'imgw_wind_direction'    => $imgwData['kierunek_wiatru'] ?? null,
            'imgw_humidity'          => $imgwData['wilgotnosc_wzgledna'] ?? null,
            'imgw_precipitation_sum' => $imgwData['suma_opadu'] ?? null,
            'imgw_pressure'          => $imgwData['cisnienie'] ?? null,
            'airly_fromDateTime'     => $airlyData['fromDateTime'] ?? null,
            'airly_tillDateTime'     => $airlyData['tillDateTime'] ?? null,
            'airly_index_value'      => $airlyData['airly_index_value'] ?? null,
        ];
    }
}