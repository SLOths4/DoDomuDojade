<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin | DoDomuDojadę</title>
    <link rel="icon" type="image/x-icon" href="../resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link href="../styles/style.css" rel="stylesheet" type="text/css">
</head>
<body>
<?php require_once __DIR__ . '/../vendor/autoload.php';?>

<!-- IMPORT HEADER -->
<?php include('./functions/admin_header.php'); ?>

<div id="announcement">
    <!-- display announceents table + delete option-->
    <?php
    include('./functions/announcement.php');
    use src\utilities\AnnouncementService;
    $announcementService = new AnnouncementService();
    $announcements = $announcementService->getAnnouncements();
    foreach ($announcements as $announcement) {
        echo $announcement;
    }

    ?>
    <!-- new announcements form -->
</div>

<!-- IMPORT FOOTER -->
<?php include('./functions/footer.php'); ?>

</body>
</html>

