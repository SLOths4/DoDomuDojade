<?php
require_once __DIR__ . '../vendor/autoload.php';

use src\utilities\AnnouncementService;
use src\utilities\UserService;

header('Content-Type: application/json');

$config = require '../config.php';
$logger = new Monolog\Logger('AppHandler');
$logger->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . '/log/app.log', Monolog\Level::Debug));

$pdo = new PDO($config['Database']['db_host']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $announcementService = new AnnouncementService($logger, $pdo);
    $userService = new UserService($logger, $pdo);

    $announcements = $announcementService->getValidAnnouncements();
    $response = [];

    foreach ($announcements as $announcement) {
        try {
            $user = $userService->getUserById($announcement['user_id']);
            $author = $user['username'] ?? 'Nieznany użytkownik';
        } catch (\Exception $e) {
            $author = 'Nieznany użytkownik';
        }

        $response[] = [
            'title' => htmlspecialchars($announcement['title']),
            'author' => $author,
            'date' => htmlspecialchars($announcement['date']),
            'validUntil' => htmlspecialchars($announcement['valid_until']),
            'text' => htmlspecialchars($announcement['text']),
        ];
    }

    echo json_encode(['success' => true, 'data' => $response]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching announcements']);
}