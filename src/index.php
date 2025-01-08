<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>DoDomuDojadę</title>
    <link rel="icon" type="image/x-icon" href="/resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link href="styles/style.css" rel="stylesheet" type="text/css">
  </head>
  <body>
    <!-- IMPORT HEADER -->
    <?php include('./functions/header.php'); ?>

    <!-- IMPORT WEATHER MODULE -->
    <?php include('./functions/weather_display.php'); ?>

    <?php include('./functions/calendar_display.php'); ?>

    <!-- IMPORT FOOTER -->
    <?php include('./functions/footer.php'); ?>

  </body>
</html>
