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
    private  HttpClientInterface $httpClient;
    private array $config;
    private string $imgw_weather_url;
    private string $air_quality_url;
    private string $airly_api_key;
    private string $airly_location_id;
    private string $airly_url;
    private Logger $logger;


    public function __construct(Logger $logger) {
        $this->httpClient = HttpClient::create();
        $this->config = require 'config.php';
        $this->logger = $logger;
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
    private function airQualityFetcher(): array {
        $this->logger->info("Rozpoczęto pobieranie danych z API Airly", ['url' => $this->air_quality_url]);
        $airly_api_data = null;
        try {
            $airly_api_data = $this->httpClient->request('GET', $this->air_quality_url, [
                'headers' => array_merge([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'apikey' => $this->airly_api_key
                ])
            ])->toArray();
            $this->logger->info("Pomyślnie pobrano dane z API Airly");
        } catch (ClientExceptionInterface $e) {
            $this->logger->error("Client error podczas pobierania danych z API Airly", ['error' => $e->getMessage()]);
            //throw new RuntimeException('Client error occurred during Air Quality API request: ' . $e->getMessage());
        } catch (DecodingExceptionInterface $e) {
            $this->logger->error("Błąd dekodowania podczas pobierania danych z API Airly", ['error' => $e->getMessage()]);
            throw new RuntimeException('Error decoding Air Quality API response: ' . $e->getMessage());
        } catch (RedirectionExceptionInterface $e) {
            $this->logger->error("Błąd przekierowania podczas pobierania danych z API Airly", ['error' => $e->getMessage()]);
            throw new RuntimeException('Redirection error during Air Quality API request: ' . $e->getMessage());
        } catch (ServerExceptionInterface $e) {
            $this->logger->error("Błąd serwera podczas pobierania danych z API Airly", ['error' => $e->getMessage()]);
            throw new RuntimeException('Server error occurred during Air Quality API request: ' . $e->getMessage());
        } catch (TransportExceptionInterface $e) {
            $this->logger->error("Błąd transportu podczas pobierania danych z API Airly", ['error' => $e->getMessage()]);
            throw new RuntimeException('Transport error occurred during Air Quality API request: ' . $e->getMessage());
        }
        
        if (empty($airly_api_data)) {
            $this->logger->error("Dane z Airly są puste. Możliwa awaria API.");
            return [];
            //throw new RuntimeException("Airly data is null. Check if API works.");
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
     * @noinspection SpellCheckingInspection
     */
    public function Weather(): array {
        $this->logger->info("Rozpoczęto pobieranie pogody z API IMGW i Airly");

        // Pobierz dane z IMGW
        try {
            $imgw_api_data = $this->httpClient->request('GET', $this->imgw_weather_url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ])->toArray();
            $this->logger->info("Pomyślnie pobrano dane z API IMGW");
        } catch (Exception $e) {
            $this->logger->error("Błąd podczas pobierania danych z API IMGW: " . $e->getMessage());
            throw new RuntimeException('Błąd podczas pobierania danych pogodowych IMGW: ' . $e->getMessage());
        }

        // Zainicjalizuj dane z Airly jako puste
        $airly_data = [
            "fromDateTime" => null,
            "tillDateTime" => null,
            "airly_index_value" => null
        ];

        // Spróbuj pobrać dane z Airly, ale ignoruj błędy
        try {
            $airly_data = $this->airQualityFetcher();
        } catch (Exception $e) {
            $this->logger->warning("Nie udało się pobrać danych z API Airly: " . $e->getMessage());
        }

        // Połącz dane z obu źródeł w odpowiedź
        return [
            "imgw_station" => $imgw_api_data['stacja'] ?? null,
            "imgw_fromDate" => $imgw_api_data['data_pomiaru'] ?? null,
            "imgw_fromHour" => $imgw_api_data['godzina_pomiaru'] ?? null,
            "imgw_temperature" => $imgw_api_data['temperatura'] ?? null,
            "imgw_wind_speed" => $imgw_api_data['predkosc_wiatru'] ?? null,
            "imgw_wind_direction" => $imgw_api_data['kierunek_wiatru'] ?? null,
            "imgw_humidity" => $imgw_api_data['wilgotnosc_wzgledna'] ?? null,
            "imgw_precipitation_sum" => $imgw_api_data['suma_opadu'] ?? null,
            "imgw_pressure" => $imgw_api_data['cisnienie'] ?? null,
            "airly_fromDateTime" => $airly_data['fromDateTime'] ?? null,
            "airly_tillDateTime" => $airly_data['tillDateTime'] ?? null,
            "airly_index_value" => $airly_data['airly_index_value'] ?? null,
        ];
    }
}