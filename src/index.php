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
    <!-- IMPORT HEADER -->
    <?php include('./functions/header.php'); ?>

    <!-- IMPORT WEATHER MODULE -->
    <div id="weather">
    <?php
    // Import get utility
    include('./utilities/get.php');
    function weather() {
        // Fetch weather
        $config = json_decode(file_get_contents("./config.json"));
        // API url
        $weather_url = $config->API[0]->url;
        // Fetching data form API
        $weather_data = get($weather_url);
        // Variables
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

    <div id="schedule">
    <?php
    include("./utilities/calendar.php");
    $calendar = new Calendar();
    $events = $calendar->get_events();

    // Display the events
    foreach ($events as $event) {
        echo "Title: " . htmlspecialchars($event['summary']) . "<br>";
        echo "Start: " . htmlspecialchars($event['start']) . "<br>";
        echo "End: " . htmlspecialchars($event['end']) . "<br>";
        echo "Description: " . htmlspecialchars($event['description']) . "<br><br>";
    }
    ?>
    </div>




    <!-- IMPORT FOOTER -->
    <?php include('./functions/footer.php'); ?>

  </body>
</html>

