<?php
namespace src\subpages;

session_start();
require_once '../../vendor/autoload.php';
require_once '../utilities/LoginService.php';
require_once '../utilities/UserService.php';

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use PDO;
use src\utilities\LoginService;
use src\utilities\UserService;

$logger = new Logger('LoginHandler');
$logger->pushHandler(new StreamHandler('../log/login.log', Level::Debug));

$_SESSION['session_error'] = null;
$_SESSION['user'] = null;
$_SESSION['user_id'] = null;

$pdo = getPdo();

$loginService = new LoginService($logger, $pdo);
$userService = new UserService($logger, $pdo);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logger->debug("Login request received");

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if ($loginService->authenticate($username, $password)) {
        $logger->info("User authenticated successfully");
        try {
            $user = $userService->getUserByUsername($username);

            if (!empty($user)) {
                $logger->info("User fetched successfully. ID: {$user['id']}");
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = $username;
                if ($user['id'] <= 0) {
                    $logger->error("Invalid user ID provided!");
                    throw new Exception("Invalid user ID provided!");
                }
            } else {
                $logger->error("Invalid user data!");
                $_SESSION['session_error'] = 'Invalid user data!';
            }
        } catch (Exception $e) {
            $logger->error('Error fetching user by username: ' . $e->getMessage());
            $_SESSION['session_error'] = 'An error occurred!';
        }
        header('Location: admin.php');
        exit;
    } else {
        $logger->warning("Authentication failed for user: $username");
        $_SESSION['session_error'] = 'Invalid credentials!';
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <title>Login</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="../styles/login.css">
</head>
<body>
    <h2>Login</h2>
    <form method="POST" action="login.php" id="form">
        <?php if ($_SESSION['session_error']): ?>
            <p style="color: red;"><?php htmlspecialchars($_SESSION['session_error'])?></p>
        <?php endif; ?>
        <label for="username"></label><input type="text" name="username" id="username" placeholder="nazwa użytkownika" required>
        <label for="password"></label><input type="password" name="password" id="password" placeholder="hasło" required>
        <button type="submit">Login</button>
    </form>
</body>
</html>