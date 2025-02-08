<?php
namespace src\subpages;

session_start();

require_once '../../vendor/autoload.php';
include('../utilities/AnnouncementService.php');
include('../utilities/UserService.php');

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use PDO;
use src\utilities\AnnouncementService;
use src\utilities\UserService;

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db_password = getenv('DB_PASSWORD');
$db_username = getenv('DB_USERNAME');
$db_host = getenv('DB_HOST');

if (!empty($db_password) and !empty($db_username)) {
    $pdo = new PDO($db_host, $db_username, $db_password);
} else {
    $pdo = new PDO($db_host);
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user = $_SESSION['user'];
$user_id = (int)$_SESSION['user_id'];

$logger = new Logger('AdminHandler');
$logger->pushHandler(new StreamHandler('../log/admin.log', Level::Debug));


$announcementService = new AnnouncementService($logger, $pdo);
$userService = new UserService($logger, $pdo);
// obsługa usuwania ogłoszeń
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    $logger->debug("delete_announcement request received");
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $logger->error("Invalid CSRF token");
        die('Invalid CSRF token');
    }

    $announcementId = $_POST['announcement_id'];

    try {
        $result = $announcementService->deleteAnnouncement($announcementId, $user_id);

        if ($result) {
            $logger->info("Announcement deleted");
            header('Location: ' . $_SERVER['PHP_SELF'] . '?delete=success');
        } else {
            $logger->error("Announcement could not be deleted");
            $_SESSION['delete_error'] = 'Failed to delete announcement';
            header('Location: ' . $_SERVER['PHP_SELF']);
        }
        exit;
    } catch (Exception $e) {
        // Log error and show generic message
        $logger->error('Announcement deletion failed', ['error' => $e->getMessage()]);
        $_SESSION['delete_error'] = 'An error occurred while deleting the announcement';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $logger->debug("add_announcement request received");
    //if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    //    $logger->error("Invalid CSRF token");
    //    die('Invalid CSRF token');
    //}

    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $text = isset($_POST['text']) ? trim($_POST['text']) : '';
    $valid_until = $_POST['valid_until'];

    try {
        $result = $announcementService->addAnnouncement($title, $text, $valid_until, $user_id);
        if ($result) {
            $logger->info("Announcement added successfully");
            header('Location: ' . $_SERVER['PHP_SELF'] . '?add=success');
        } else {
            $logger->error("Announcement adding failed");
            $_SESSION['add_error'] = 'Failed to add announcement';
            header('Location: ' . $_SERVER['PHP_SELF']);
        }
        exit;
    } catch (Exception $e) {
        $logger->error('Announcement adding failed', ['error' => $e->getMessage()]);
        $_SESSION['add_error'] = 'Failed to add announcement';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $logger->debug("add_user request received");
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $logger->error("Invalid CSRF token");
        die('Invalid CSRF token');
    }

    if (!isset($_POST['username']) || !isset($_POST['password']) || empty(trim($_POST['username'])) || empty(trim($_POST['password']))) {
        $logger->error("Username and password are required");
        $_SESSION['user_add_error'] = "Username and password are required!";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }


    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $result = $userService->addUser($username, $password);
        if ($result) {
            $logger->info("User added successfully");
            header('Location: ' . $_SERVER['PHP_SELF'] . '?user_add=success');
        } else {
            $logger->error("User adding failed");
            $_SESSION['user_add_error'] = 'Failed to add user';
            header('Location: ' . $_SERVER['PHP_SELF']);
        }
        exit;
    } catch (Exception $e) {
        $logger->error('User adding failed', ['error' => $e->getMessage()]);
        $_SESSION['user_add_error'] = 'Failed to add user';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $logger->debug("delete_user request received");
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $logger->error("Invalid CSRF token");
        die('Invalid CSRF token');
    }


    $del_user_id = trim($_POST['user_id']);

    try {
        $result = $userService->deleteUser($del_user_id);
        if ($result) {
            $logger->info("User deleted successfully");
            header('Location: ' . $_SERVER['PHP_SELF'] . '?user_delete=success');
        } else {
            $logger->error("User deletion failed");
            $_SESSION['user_delete_error'] = 'Failed to delete user';
            header('Location: ' . $_SERVER['PHP_SELF']);
        }
        exit;
    } catch (Exception $e) {
        $logger->error('User deletion failed', ['error' => $e->getMessage()]);
        $_SESSION['user_delete_error'] = 'Failed to delete user';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}


// Ustawianie stanu checkboxa na podstawie przesłania formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['display-valid-announcements-only'])) {
        // Zaznaczenie checkboxa (tylko ważne ogłoszenia)
        $_SESSION['display_valid_announcements_only'] = true;
    } else {
        // Odznaczenie checkboxa (wszystkie ogłoszenia)
        $_SESSION['display_valid_announcements_only'] = false;
    }
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Panel | DoDomuDojadę</title>
    <link rel="icon" type="image/x-icon" href="../resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link href="../styles/style.css" rel="stylesheet" type="text/css">
    <link href="../styles/admin.css" rel="stylesheet" type="text/css">
    <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
</head>
<body>
<!-- IMPORT HEADER -->
<?php include('../functions/header.php'); ?>

<h1>Witaj, <?= htmlspecialchars($user) ?>!</h1>

<button onclick="location.href = 'logout.php';"><i class="fa-solid fa-right-from-bracket"></i> Wyloguj się</button>
<button onclick="window.open('../index.php', '_blank');"><i class="fa-solid fa-display"></i> Wyświetlaj informacje</button>

<div id="announcement">
    <?php
    if (isset($_GET['delete']) && $_GET['delete'] == 'success') {
        echo '<div class="sucess"> Ogłoszenie zostało usunięte!</div>';
    }
    if (isset($_SESSION['delete_error'])) {
        echo '<div class="error">' . htmlspecialchars($_SESSION['delete_error']) . '</div>';
        unset($_SESSION['delete_error']);
    }

    if (isset($_GET['add']) && $_GET['add'] == 'success') {
        echo '<div class="success">Ogłoszenie zostało dodane!</div>';
    }
    if (isset($_SESSION['add_error'])) {
        echo '<div class="error">' . htmlspecialchars($_SESSION['add_error']) . '</div>';
        unset($_SESSION['add_error']);
    }

    // Sprawdzanie, czy wyświetlać tylko ważne ogłoszenia (domyślnie "wszystkie")
    $showOnlyValid = $_SESSION['display_valid_announcements_only'] ?? false;
    ?>

    <form method="POST" action="admin.php" id="display-valid-announcements-only">
        <label>
            <!-- Jeśli w sesji jest zapisane, że checkbox ma być włączony, to dodawany jest atrybut "checked" -->
            <input type="checkbox"
                   name="display-valid-announcements-only"
                   onchange="this.form.submit();"
                   placeholder="display only valid announcements"
                <?php if ($showOnlyValid) echo 'checked'; ?>>
            Wyświetlaj tylko ważne ogłoszenia
        </label>
    </form>


    <form method="POST" action="admin.php" id="form">
        <label>
            <input type="text" name="title" placeholder="Title">
        </label>
        <label>
            <input type="text" name="text" placeholder="Text">
        </label>
        <label>
            <input type="date" name="valid_until" placeholder="Valid until">
        </label>
        <input type="submit" name="add_announcement" value="Add">
        <input type="hidden" name="csrf_token" value="<?php htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="user_id" value="<?php htmlspecialchars($_SESSION['user_id']) ?>">
    </form>

    <?php

    // Pobieranie ogłoszeń na podstawie stanu checkboxa
    if ($showOnlyValid) {
        $announcements = $announcementService->getValidAnnouncements(); // Tylko ważne ogłoszenia
    } else {
        $announcements = $announcementService->getAnnouncements(); // Wszystkie ogłoszenia
    }


    $user_service = new UserService($logger, $pdo);
    foreach ($announcements as $announcement) {
        try {
            $user = $user_service->getUserById($announcement['user_id']);
            $author_username = $user['username'] ?? 'Nieznany użytkownik';
        } catch (Exception $e) {
            $author_username = $announcement['user_id'] ?? 'Nieznany autor';
        }
        echo "<div id='announcement'>";
        // Announcement
        echo "<h3>" . htmlspecialchars($announcement['title']) . "</h3><br>";
        echo "Autor: " . $author_username . " | " . htmlspecialchars($announcement['date']) . "<br>";
        echo "Ważne do: " . htmlspecialchars($announcement['valid_until']) . "<br>";
        echo htmlspecialchars($announcement['text']) . "<br><br>";
        // Delete form
        echo "<form method='POST' onsubmit='return confirm(\"Are you sure you want to delete this announcement?\");'>";
        echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token']) . "'>";
        echo "<input type='hidden' name='announcement_id' value='" . htmlspecialchars($announcement['id']) . "'>";
        echo "<button type='submit' name='delete_announcement'>Usuń</button>";
        echo "</form>";
        // Edit form
        // TODO Dodanie edycji ogłoszenia
        echo "<form method='POST' onsubmit='return confirm(\"Are you sure you want to make changes to this announcement?\");'>";
        echo "<input type='text' name='title' placeholder='Title' >";
        echo "<input type='text' name='text' placeholder='Text'>";
        echo "<input type='date' name='valid_until' placeholder='Valid until'>";
        echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token']) . "'>";
        echo "<input type='hidden' name='announcement_id' value='" . htmlspecialchars($announcement['id']) . "'>";
        echo "<button type='submit' name='edit_announcement'>Edytuj</button>";
        echo "</form>";
        // Div close tag
        echo "</div>";
    }

    ?>
</div>

<div id="users">
        <?php
        if (isset($_GET['user_add']) && $_GET['user_add'] == 'success') {
            echo '<div class="success">Uzytkownik został dodany!</div>';
        }
        if (isset($_SESSION['user_add_error'])) {
            echo '<div class="error">' . htmlspecialchars($_SESSION['user_add_error']) . '</div>';
            unset($_SESSION['user_add_error']);
        }
        if (isset($_GET['user_delete']) && $_GET['user_delete'] == 'success') {
            echo '<div class="success">Uzytkownik został usunięty!</div>';
        }
        if (isset($_SESSION['user_delete_error'])) {
            echo '<div class="error">' . htmlspecialchars($_SESSION['user_delete_error']) . '</div>';
            unset($_SESSION['user_delete_error']);
        }

        $users = $userService->getUsers();
        foreach ($users as $user) {
            echo "<div id='user'>";
            echo "<h3>" . htmlspecialchars($user['username']) . "</h3><br>";
            echo "ID". htmlspecialchars($user['id']) . "<br>";
            echo "<form method='POST' action='admin.php' onsubmit='return confirm(\"Are you sure you want to delete this user?\");'>";
            echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token']) . "'>";
            echo "<input type='hidden' name='user_id' value='" . htmlspecialchars($user['id']) . "'>";
            echo "<button type='submit' name='delete_user'>Usuń</button>";
            echo "</form>";
        }

        echo "<form method='POST' action='admin.php' onsubmit='return confirm(\"Are you sure you want to add this user?\");'>";
        echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token']) . "'>";
        echo "<input type='text' name='username'>";
        echo "<input type='text' name='password'>";
        echo "<button type='submit' name='add_user'>Dodaj uzytkownika</button>";
        echo "</form>";
        ?>
</div>

<!-- IMPORT FOOTER -->
<?php include('../functions/footer.php'); ?>

</body>
</html>