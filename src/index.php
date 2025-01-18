<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>DoDomuDojadę</title>
    <link rel="icon" type="image/x-icon" href="resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link href="styles/style.css" rel="stylesheet" type="text/css">
  </head>
  <body>
    <?php  require_once __DIR__ . '/../vendor/autoload.php';?>
    <!-- IMPORT HEADER -->
    <?php include('./functions/header.php'); ?>

    <!-- IMPORT WEATHER MODULE -->
    <div id="weather">
        <?php
        include('./utilities/WeatherService.php');
        use src\utilities\WeatherService;
        $weatherService = new WeatherService();
        $weatherServiceResponse = $weatherService->Weather();?>
        <h2>Dzisiejsza pogoda</h2>
        <?php
            // przykład
            echo "Dzisiejsza temperatura: " . htmlspecialchars($weatherServiceResponse['imgw_temperature']) . "C" . "<br>";
            echo "Ciśnienie: ". htmlspecialchars($weatherServiceResponse['imgw_pressure']) . "hPa". "<br>";
            echo "Wysokość indeksu JAKIŚTAM: ". htmlspecialchars($weatherServiceResponse['airly_index_value']). "<br>";
        ?>
    </div>

    <div id="tram">
        <?php
        include('./utilities/TramService.php');
        use src\utilities\TramService;
        $tram_service = new TramService();
        $tram_service_departures = $tram_service->getTimes("AWF73");
        echo $tram_service_departures['success']['times'][0]['line'] . " linia" . "<br>";
        echo $tram_service_departures['success']['times'][0]['minutes'] . " minut";

        ?>
    </div>

    <!-- IMPORT FOOTER -->
    <?php include('./functions/footer.php'); ?>

  </body>
</html>

