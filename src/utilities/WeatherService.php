<?php
namespace src\utilities;

use Exception;
use RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class WeatherService {
    private  HttpClientInterface $httpClient;
    private array $config;
    private string $imgw_weather_url;
    private string $air_quality_url;
    private string $airly_api_key;
    private string $airly_location_id;
    private string $airly_url;


    public function __construct() {
        $this->httpClient = HttpClient::create();
        $this->config = require 'config.php';
        $this->imgw_weather_url = $this->config['API'][0]['url'] ?? '';
        $this->airly_url = $this->config['Airly']['AirlyEndpoint'] ?? '';
        $this->airly_api_key = $this->config['Airly']['AirlyApiKey'] ?? '';
        $this->airly_location_id = ltrim($this->config['Airly']['AirlyLocationId'] ?? '', '/');
        $this->air_quality_url = $this->airly_url . urlencode($this->airly_location_id);
    }

    /**
     * Pobiera dane z API Airly ze stacji podanej w configu
     * @return array
     */
    private function airQualityFetcher():array {
        try {
            $airly_api_data = $this->httpClient->request('GET', $this->air_quality_url, [
                'headers' => array_merge([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'apikey' => $this->airly_api_key
                ])
            ])->toArray();
        } catch (ClientExceptionInterface $e) {
            throw new RuntimeException('Client error occurred during Air Quality API request: ' . $e->getMessage());
        } catch (DecodingExceptionInterface $e) {
            throw new RuntimeException('Error decoding Air Quality API response: ' . $e->getMessage());
        } catch (RedirectionExceptionInterface $e) {
            throw new RuntimeException('Redirection error during Air Quality API request: ' . $e->getMessage());
        } catch (ServerExceptionInterface $e) {
            throw new RuntimeException('Server error occurred during Air Quality API request: ' . $e->getMessage());
        } catch (TransportExceptionInterface $e) {
            throw new RuntimeException('Transport error occurred during Air Quality API request: ' . $e->getMessage());
        }
        
        if ($airly_api_data == null) {
            throw new RuntimeException("Airly data is null. Check if API works.");
        }
        
        return [
            "fromDateTime" => $airly_api_data['current']['fromDateTime'],
            "tillDateTime" => $airly_api_data['current']['tillDateTime'],
            "airly_index_value" => $airly_api_data['current']['indexes'][0]['level']
        ];
    }

    /**
     * Function fetching weather from imgw and airly API
     * @return array
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * TODO ignore typos @t
     */
    public function Weather():array{
        try {
        $imgw_api_data = $this->httpClient->request('GET', $this->imgw_weather_url, [
            'headers' => array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
        ])->toArray();
        } catch (Exception $e) {
            throw new RuntimeException('API request failed: ' . $e->getMessage());
        }

        $airly_data = $this->airQualityFetcher();

        if ($airly_data == null) {
            return [];
        }

        return [
            "imgw_station" => $imgw_api_data['stacja'],
            "imgw_fromDate" => $imgw_api_data['data_pomiaru'],
            "imgw_fromHour" => $imgw_api_data['godzina_pomiaru'],
            "imgw_temperature" => $imgw_api_data['temperatura'],
            "imgw_wind_speed" => $imgw_api_data['predkosc_wiatru'],
            "imgw_wind_direction" => $imgw_api_data['kierunek_wiatru'],
            "imgw_humidity" => $imgw_api_data['wilgotnosc_wzgledna'],
            "imgw_precipitation_sum" => $imgw_api_data['suma_opadu'],
            "imgw_pressure" => $imgw_api_data['cisnienie'],
            "airly_fromDateTime" => $airly_data['fromDateTime'],
            "airly_tillDateTime" => $airly_data['tillDateTime'],
            "airly_index_value" => $airly_data['airly_index_value']
        ];
    }
}