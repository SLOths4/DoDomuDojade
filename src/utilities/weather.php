<?php
use Symfony\Component\HttpClient\HttpClient;
class WeatherService {
    public $config = json_decode(file_get_contents("./config.json"));
    public $imgw_weather_url = $config->API[0]->url;
    private function imgwWeatherFetcher() {

    }

    private function airQuaityFetcher() {}

    public function Weather(){}

}


function weather() {
// Fetch weather

// API url

// Fetching data form API
$weather_data = get($weather_url);
// Variables
$data_pomiaru = $weather_data["data_pomiaru"];
$godzina_pomiaru = $weather_data["godzina_pomiaru"];
$kierunek_wiatru = $weather_data["kierunek_wiatru"];
$wilgotnosc_wzgledna = $weather_data["wilgotnosc_wzgledna"];
$suma_opadu = $weather_data["suma_opadu"];
$cisnienie = $weather_data["cisnienie"];



}
?>