<?php
session_start();
require_once '../../vendor/autoload.php';
require_once '../utilities/LoginService.php';

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use src\utilities\LoginService;

$logger = new Logger('LoginHandler');
$logger->pushHandler(new StreamHandler('../log/login.log', Level::Debug));

$config = require '../config.php';
$db_host = $config['Database']['db_host'];

$logger->debug("Database host retrieved from config: $db_host");

$pdo = new PDO($db_host);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$logger->debug("PDO connection established with error mode set to ERRMODE_EXCEPTION");

$loginService = new LoginService($logger, $pdo);
$logger->warning("This is unsafe solution. Use for development purposes only!");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logger->debug("POST request received");
    $username = $_POST['username'];
    $password = $_POST['password'];
    $logger->debug("Username: $username received via POST");

    if ($loginService->authenticate($username, $password)) {
        $logger->info("User $username authenticated successfully");
        $_SESSION['user'] = $username;
        header('Location: admin.php');
        exit;
    } else {
        $logger->warning("Authentication failed for user: $username");
        $error = 'Invalid credentials!';
    }
}?>

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

        <?php if ($error): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>
        <input type="text" name="username" id="username" placeholder=" username" required>
        <input type="password" name="password" id="password" placeholder="  password" required>
        <button type="submit">Login</button>
    </form>
</body>
</html>