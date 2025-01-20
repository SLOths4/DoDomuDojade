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
    <div id="weather" class="div">
        <?php
        include('./utilities/WeatherService.php');
        use src\utilities\WeatherService;
        $weatherService = new WeatherService();
        $weatherServiceResponse = $weatherService->Weather();?>
        <h2>Dzisiejsza pogoda</h2>
        <?php
            // przykład
            echo "Dzisiejsza pogoda: " . htmlspecialchars($weatherServiceResponse['imgw_weather']) . "<br>";
            echo "Dzisiejsza temperatura: " . htmlspecialchars($weatherServiceResponse['imgw_temperature']) . "C" . "<br>";
            echo "Ciśnienie: ". htmlspecialchars($weatherServiceResponse['imgw_pressure']) . "hPa". "<br>";
            echo "Wysokość indeksu JAKIŚTAM: ". htmlspecialchars($weatherServiceResponse['airly_index_value']). "<br>";

        ?>
    </div>

    <div id="tram" class="div">
        <?php
        include('./utilities/TramService.php');
        use src\utilities\TramService;
        $tram_service = new TramService();
        $tram_service_departures = $tram_service->getTimes("AWF73");
        echo $tram_service_departures['success']['times'][0]['line'] . " linia" . "<br>";
        echo $tram_service_departures['success']['times'][0]['minutes'] . " minut";

        ?>
    </div>

    <div id="announcements" class="div">
        <?php
        include('./utilities/AnnouncementService.php');
        use src\utilities\AnnouncementService;

        $announcement_service = new AnnouncementService();
        $announcement_service_announcements = $announcement_service->getAnnouncements();

        foreach ($announcement_service_announcements as $announcement) {
            // Wyświetlamy wartości każdego rekordu
            echo "<div class=\"div\">";
            echo "Tytuł wpisu: " . htmlspecialchars($announcement['title']) . "<br>";
            echo "Data wysłania: " . htmlspecialchars($announcement['date']) . "<br>";
            echo "Autor: " . htmlspecialchars($announcement['user_id']) . "<br>";
            echo "Ważne do: " . htmlspecialchars($announcement['valid_until']) . "<br>";
            echo htmlspecialchars($announcement['text']) . "<br><br>";
            echo "</div>";
        }
        ?>
    </div>
    <!-- IMPORT FOOTER -->
    <?php include('./functions/footer.php'); ?>

  </body>
</html>

