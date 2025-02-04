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
      <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
  </head>
  <body>
    <?php
    require_once __DIR__ . '/../vendor/autoload.php';

    use Monolog\Handler\StreamHandler;
    use Monolog\Level;
    use Monolog\Logger;
    use src\utilities\WeatherService;

    $config = require './config.php';
    $db_host = $config['Database']['db_host'];

    $logger = new Logger('AppHandler');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/log/app.log', Level::Debug));

    $pdo = new PDO($db_host);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    ?>
    <div class="div"><img src="resources/logo_samo_kolor.png" alt="logo" width="30" height="30"></div>
    <!-- IMPORT HEADER -->
    <?php include('./functions/header.php'); ?>

    <!-- IMPORT WEATHER MODULE -->
    <div id="weather" class="div">
        <h2>Dzisiejsza pogoda</h2>
        <?php
        include('./utilities/WeatherService.php');


        $weatherService = new WeatherService();
        try {
        $weatherServiceResponse = $weatherService->Weather();

        echo "<i class='fa-solid fa-temperature-full'></i>  " . htmlspecialchars($weatherServiceResponse['imgw_temperature']) . "&deg;C" . "<br>";
        echo "<i class='fa-solid fa-gauge'></i>  ". htmlspecialchars($weatherServiceResponse['imgw_pressure']) . " hPa". "<br>";
        echo "<i class='fa-solid fa-lungs'></i>  ". htmlspecialchars($weatherServiceResponse['airly_index_value']). "<br>";
        } catch (Exception $e) {
            echo "No weather today :-)";
        }
        ?>
    </div>

    <div id="tram" class="div">
        <h2>Odjazdy tramwajów z przystanku AWF 73</h2>
        <?php
        include('./utilities/TramService.php');
        use src\utilities\TramService;

        $tram_service = new TramService($logger, "https://www.peka.poznan.pl/vm/method.vm");
        try {
            $tram_service_departures = $tram_service->getTimes("AWF73");

            if (isset($tram_service_departures['success']['times']) && is_array($tram_service_departures['success']['times'])) {
                echo "<h2>Odjazdy tramwajów:</h2>";
                foreach ($tram_service_departures['success']['times'] as $departure) {
                    echo "<div class='tram-line'>";
                    echo "<i class='fa-solid fa-train'></i>  " . htmlspecialchars($departure['line']) . "<br>";
                    echo "<i class='fa-solid fa-clock'></i>  " . htmlspecialchars($departure['minutes']) . " minut<br>";
                    echo "<i class='fa-solid fa-location-arrow'></i> " . htmlspecialchars($departure['direction']) . "<br>";
                    echo "</div>";
                }
            } else {
                echo "Brak danych o odjazdach.";
            }
        } catch (Exception $e) {
            echo "No departures today :-)";
        }
        ?>
    </div>

    <div id="announcements" class="div">
        <h2>Ogłoszenia</h2>
        <?php
        include('./utilities/AnnouncementService.php');
        include('./utilities/UserService.php');
        use src\utilities\AnnouncementService;
        use src\utilities\UserService;

        try {
            $announcement_service = new AnnouncementService($logger, $pdo);
            $user_service = new UserService($logger, $pdo);
            $announcement_service_announcements = $announcement_service->getValidAnnouncements();
            foreach ($announcement_service_announcements as $announcement) {


                try {
                    $user = $user_service->getUserById($announcement['user_id']);
                    $author_username = $user['username'] ?? 'Nieznany użytkownik';
                } catch (Exception $e) {
                    $author_username = $announcement['user_id'] ?? 'Nieznany autor';
                }
                echo "<div class='announcement'>";
                echo "<h3>" . htmlspecialchars($announcement['title']) . "</h3><br>";
                echo htmlspecialchars($author_username) . " | " . htmlspecialchars($announcement['date']) . "<br>";
                echo "Ważne do: " . htmlspecialchars($announcement['valid_until']) . "<br>";
                echo htmlspecialchars($announcement['text']) . "<br><br>";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "No announcements available.";
        }
        ?>
    </div>
    <!-- IMPORT FOOTER -->
    <?php include('functions/footer.php'); ?>
  </body>
</html>

