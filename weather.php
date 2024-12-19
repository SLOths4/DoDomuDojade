
<div id="weather">
    <?php
    include('./');
    function weather() {
        // Fetch weather
        $config = json_decode(file_get_contents("config.json"));
        $weather_url = $config->API[0]->url;
        $weather_data = $_GET[$weather_url];
        echo "<p>$weather_url</p>";
        echo "<p>$weather_data</p>";
    }
    weather();

    ?>
</div>