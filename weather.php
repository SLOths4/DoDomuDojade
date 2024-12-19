
<div id="weather">
    <?php
    include('./utilities/get.php');
    function weather() {
        // Fetch weather
        $config = json_decode(file_get_contents("config.json"));
        // API url
        $weather_url = $config->API[0]->url;
        // Fetching data form API
        $weather_data = get($weather_url);
        // Variables
        $id_stacji = $weather_data["id_stacji"];
        $data_pomiaru = $weather_data["data_pomiaru"];
        $godzina_pomiaru = $weather_data["godzina_pomiaru"];
        $kierunek_wiatru = $weather_data["kierunek_wiatru"];
        $wilgotnosc_wzgledna = $weather_data["wilgotnosc_wzgledna"];
        $suma_opadu = $weather_data["suma_opadu"];
        $cisnienie = $weather_data["cisnienie"];
        
        echo "<p> Data i godzina pomiaru: ".$data_pomiaru.", ".$godzina_pomiaru."</p>";
        echo "<p> Kierunek wiatru: ".$kierunek_wiatru."°</p>";
        echo "<p> Wilgotność względna: ".$wilgotnosc_wzgledna."</p>";
        echo "<p> Suma opadu: ".$suma_opadu."</p>";
        echo "<p> Ciśnienie: ".$cisnienie."hPa</p>";
        
    }
    weather();

    ?>
</div>