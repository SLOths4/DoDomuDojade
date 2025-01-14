<?php
namespace App\utilities;

class WeatherService {
    private ApiClient $ApiClient;
    private $config;
    private string $imgw_weather_url;
    public function __construct() {
        $this->ApiClient = new ApiClient();
        $this->config = json_decode(file_get_contents("./config.json"));
        $this->imgw_weather_url = $this->config->API[0]->url;
    }
    
    private function imgwWeatherFetcher() {
        $this->ApiClient->get($this->imgw_weather_url);
    }

    private function airQualityFetcher() {}

    public function Weather(){}

}