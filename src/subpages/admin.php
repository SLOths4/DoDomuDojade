<?php
session_start();

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use src\utilities\AnnouncementService;
use src\utilities\LoginService;

require_once '../../vendor/autoload.php';
include('../utilities/AnnouncementService.php');

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}



if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user = $_SESSION['user'];
$logger = new Logger('LoginHandler');
$logger->pushHandler(new StreamHandler('../log/admin.log', Level::Debug));
$announcementService = new AnnouncementService($logger);

$logger->debug("Session user value:", ['user' => $_SESSION['user']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    $announcementId = $_POST['announcement_id'];

    try {
        $result = $announcementService->deleteAnnouncement($announcementId, 1);

        if ($result) {
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF'] . '?delete=success');
            exit;
        } else {
            // Store error in session to display after redirect
            $_SESSION['delete_error'] = 'Failed to delete announcement';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (Exception $e) {
        // Log error and show generic message
        $logger->error('Announcement deletion failed', ['error' => $e->getMessage()]);
        $_SESSION['delete_error'] = 'An error occurred while deleting the announcement';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $logger->debug("add_announcement POST request received");
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $text = isset($_POST['text']) ? trim($_POST['text']) : '';
    $valid_until = $_POST['valid_until'];
    //$user_id = $_POST['user_id'];

    $announcementService->addAnnouncement($title, $text, $valid_until, 1);
}

?>

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
    <link href="../styles/admin.css" rel="stylesheet" type="text/css">
</head>
<body>
<!-- IMPORT HEADER -->
<?php include('../functions/header.php'); ?>

<h1>Welcome, <?= htmlspecialchars($user) ?>!</h1>

<button onclick="location.href = 'logout.php';">Logout</button>
<button onclick="location.href = '../index.php';" >Wyświetlaj informacje</button>

<div id="announcement">
    <!-- display announcements table + delete option-->
    <?php
    //$success = '';
    //$error = '';
    if (isset($_GET['delete']) && $_GET['delete'] == 'success') {
        //$success = "Announcement deleted successfully!";
        echo '<div class="sucess"> Announcement dedleted sucessfully!</div>';
    }
    if (isset($_SESSION['delete_error'])) {
        echo '<div class="error">' . htmlspecialchars($_SESSION['delete_error']) . '</div>';
        unset($_SESSION['delete_error']);
    }
    ?>

    <form method="POST" action="admin.php" id="form">
        <input type="text" name="title" placeholder="Title">
        <input type="text" name="text" placeholder="Text">
        <input type="date" name="valid_until" placeholder="Valid until">
        <input type="submit" name="add_announcement" value="Add">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <!--<input type="hidden" name="user_id" value="< ?php htmlspecialchars($user) ?>"> -->
    </form>

    <?php
    $announcements = $announcementService->getAnnouncements();
    foreach ($announcements as $announcement) {
        // Wyświetlamy wartości każdego rekordu
        echo "<div id='announcement'>";
        echo "<h3>" . htmlspecialchars($announcement['title']) . "</h3><br>";
        echo "Autor: " . htmlspecialchars($announcement['user_id'] ) . " | " . htmlspecialchars($announcement['date']) . "<br>";
        echo "Ważne do: " . htmlspecialchars($announcement['valid_until']) . "<br>";
        echo htmlspecialchars($announcement['text']) . "<br><br>";
        echo "<form method='POST' onsubmit='return confirm(\"Are you sure you want to delete this announcement?\");'>";
        echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token']) . "'>";
        echo "<input type='hidden' name='announcement_id' value='" . htmlspecialchars($announcement['id']) . "'>";
        echo "<button type='submit' name='delete_announcement'>Delete</button>";
        echo "</form>";
        //echo '<div class="success">' . $success . '</div>';
        echo "</div>";
    }

    ?>
    <!-- new announcements form -->
</div>

<!-- IMPORT FOOTER -->
<?php include('../functions/footer.php'); ?>

</body>
</html>