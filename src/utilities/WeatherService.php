<?php
namespace src\utilities;

class WeatherService {
    private ApiClient $ApiClient;
    private $config;
    private string $imgw_weather_url;
    public function __construct() {
        $this->ApiClient = new ApiClient();
        $this->config = require 'config.php';
        $this->imgw_weather_url = $this->config->API[0]->url;
    }

    private function airQualityFetcher() {}

    public function Weather(){
        $imgw_api_data = $this->ApiClient->get($this->imgw_weather_url);

    }

}