<?php
namespace src\models;

use Exception;
use RuntimeException;
use src\core\Model;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherModel extends Model
{
    private  HttpClientInterface $httpClient;
    private string $imgwWeatherUrl;
    private string $airQualityUrl;
    private string $airlyApiKey;
    private string $airlyLocationId;
    private string $airlyUrl;


    public function __construct() {
        $this->httpClient = HttpClient::create();

        $this->imgwWeatherUrl = $this->getEnvVariable('IMGW_WEATHER_URL') ?? self::$logger->error("IMGW endpoint is missing.") && throw new RuntimeException('IMGW endpoint is missing.');
        $this->airlyUrl = $this->getEnvVariable('AIRLY_ENDPOINT') ?? self::$logger->error("Airly endpoint is missing.") && throw new RuntimeException('Airly endpoint is missing.');
        $this->airlyApiKey = $this->getEnvVariable('AIRLY_API_KEY') ?? self::$logger->error('Airly API key is missing.') && throw new RuntimeException('Airly API key is missing.');
        $this->airlyLocationId = ltrim($this->getEnvVariable('AIRLY_LOCATION_ID'), '/') ?? self::$logger->error('Airly location ID is missing.') && throw new RuntimeException('Airly location ID is missing.');
        $this->airQualityUrl = $this->airlyUrl . $this->airlyLocationId;
    }

    /**
     * Wykonuje zapytanie HTTP do podanego URL z opcjonalnymi nagłówkami.
     *
     * @param string $url
     * @param array $headers
     *
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
        ClientExceptionInterface |
        RedirectionExceptionInterface |
        ServerExceptionInterface |
        TransportExceptionInterface |
        DecodingExceptionInterface $e
        ) {
            self::$logger->error("Error while fetching data from ". $url . " " .  $e->getMessage());
            throw new Exception("Error while fetching data from ". $url . " " .  $e->getMessage());
        } catch (Exception $e) {
            self::$logger->error("Unexpected error occurred while fetching data from " . $url . " " .  $e->getMessage());
            throw new Exception("Unexpected error occurred while fetching data from " . $url . " " .  $e->getMessage());
        }
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
     * @throws Exception
     */
    private function getAirlyData(): array
    {
        self::$logger->info("Rozpoczęto pobieranie danych z API Airly", ['url' => $this->airQualityUrl]);

        $headers = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'apikey'       => $this->airlyApiKey,
            'Accept-Language' => 'pl',
        ];

        try {
            $data = $this->fetchData($this->airQualityUrl, $headers);
            self::$logger->info("Pomyślnie pobrano dane z API Airly");
        } catch (RuntimeException $e) {
            self::$logger->warning("Nie udało się pobrać danych z API Airly: " . $e->getMessage());
            return $this->extractAirlyData([]);
        }

        if (empty($data)) {
            self::$logger->error("Dane z Airly są puste. Możliwa awaria API.");
            throw new Exception("API Airly returned empty data. Possible API failure.");
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
        self::$logger->debug("Rozpoczęto pobieranie danych z API IMGW");

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        try {
            $data = $this->fetchData($this->imgwWeatherUrl, $headers);
            self::$logger->debug("Pomyślnie pobrano dane z API IMGW");
        } catch (Exception $e) {
            self::$logger->error("Błąd podczas pobierania danych z API IMGW: " . $e->getMessage());
            throw new RuntimeException("Error while fetching IMGW weather data: " . $e->getMessage());
        }

        return $data;
    }

    /**
     * Łączy dane pogodowe z IMGW oraz dane jakości powietrza z Airly.
     *
     * @return array
     *
     * @throws Exception
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
            'airly_index_advice'     => $airlyData['airly_index_advice'] ?? null,
            'airly_index_colour'     => $airlyData['airly_index_colour'] ?? null,
            'airly_index_description' => $airlyData['airly_index_description'] ?? null,
        ];
    }
}