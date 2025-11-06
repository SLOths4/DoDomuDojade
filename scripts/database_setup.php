<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Europe/Warsaw');

$databasePath = trim(readline('Enter the full path to the database (e.g., /path/to/database.db): '));

if (empty($databasePath)) {
    die("Error: Database path cannot be empty.\n");
}

$directory = dirname($databasePath);
if (!is_dir($directory) && !mkdir($directory, 0777, true)) {
    die("Error: Cannot create directory '$directory'. Check permissions.\n");
}

try {
    $db = new SQLite3($databasePath);
    echo "Connected to database: $databasePath\n";
    $db->exec('PRAGMA foreign_keys = ON;');
} catch (Exception $e) {
    die("Failed to connect to database '$databasePath': " . $e->getMessage() . "\n");
}

$result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
$isNewDatabase = !$result->fetchArray();

$createTables = <<<SQL
CREATE TABLE IF NOT EXISTS modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    module_name TEXT NOT NULL,
    is_active INTEGER DEFAULT 0 NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at DATE NOT NULL
);

CREATE TABLE IF NOT EXISTS announcements (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    text TEXT NOT NULL,
    date DATE NOT NULL,
    valid_until DATE NOT NULL,
    user_id INTEGER NOT NULL REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS countdowns (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    count_to DATE NOT NULL,
    user_id INTEGER NOT NULL REFERENCES users(id)
);
SQL;

try {
    $db->exec($createTables);
    echo $isNewDatabase ? "Database and tables created successfully.\n" : "Tables ensured successfully.\n";
} catch (Exception $e) {
    $db->close();
    die("Failed to create tables: " . $e->getMessage() . "\n");
}

// Insert default modules if the database is new
if ($isNewDatabase) {
    $defaultModules = [
        ['name' => 'announcements', 'is_active' => 1, 'start_time' => date('Y-m-d H:i:s'), 'end_time' => '2030-12-31 23:59:59'],
        ['name' => 'calendar', 'is_active' => 1, 'start_time' => date('Y-m-d H:i:s'), 'end_time' => '2030-12-31 23:59:59'],
        ['name' => 'countdown', 'is_active' => 1, 'start_time' => date('Y-m-d H:i:s'), 'end_time' => '2030-12-31 23:59:59'],
        ['name' => 'tram', 'is_active' => 1, 'start_time' => date('Y-m-d H:i:s'), 'end_time' => '2030-12-31 23:59:59'],
        ['name' => 'weather', 'is_active' => 1, 'start_time' => date('Y-m-d H:i:s'), 'end_time' => '2030-12-31 23:59:59']
    ];

    $insertModule = $db->prepare('INSERT INTO modules (module_name, is_active, start_time, end_time) VALUES (:module_name, :is_active, :start_time, :end_time)');

    foreach ($defaultModules as $module) {
        $insertModule->bindValue(':module_name', $module['name']);
        $insertModule->bindValue(':is_active', $module['is_active'], SQLITE3_INTEGER);
        $insertModule->bindValue(':start_time', $module['start_time']);
        $insertModule->bindValue(':end_time', $module['end_time']);

        try {
            $insertModule->execute();
            echo "Module '{$module['name']}' inserted successfully.\n";
        } catch (Exception $e) {
            $db->close();
            die("Failed to insert module '{$module['name']}': " . $e->getMessage() . "\n");
        }
        $insertModule->reset();
    }
}

$username = trim(readline('Enter username: '));
if (empty($username)) {
    $db->close();
    die("Error: Username cannot be empty.\n");
}

$password = trim(readline('Enter password: '));
if (empty($password)) {
    $db->close();
    die("Error: Password cannot be empty.\n");
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$createdAt = date('Y-m-d H:i:s');

// Insert user
$insertUser = $db->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (:username, :password_hash, :created_at)');
$insertUser->bindValue(':username', $username);
$insertUser->bindValue(':password_hash', $hashedPassword);
$insertUser->bindValue(':created_at', $createdAt);

try {
    $insertUser->execute();
    echo "User '$username' inserted successfully.\n";
} catch (Exception $e) {
    $db->close();
    die("Failed to insert user '$username': " . $e->getMessage() . "\n");
}

$verifyUser = $db->prepare('SELECT id, username, password_hash, created_at FROM users WHERE username = :username');
$verifyUser->bindValue(':username', $username);
$result = $verifyUser->execute();

if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "User ID: " . $row['id'] . "\n";
    echo "Username: " . $row['username'] . "\n";
    echo "Password (hashed): " . $row['password_hash'] . "\n";
    echo "Created At: " . $row['created_at'] . "\n";
} else {
    echo "Warning: Could not retrieve user '$username' for verification.\n";
}

// Optional: Verify module insertion
if ($isNewDatabase) {
    $result = $db->query('SELECT id, module_name, is_active, start_time, end_time FROM modules');
    echo "\nDefault Modules:\n";
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "Module ID: " . $row['id'] . "\n";
        echo "Module Name: " . $row['module_name'] . "\n";
        echo "Is Active: " . $row['is_active'] . "\n";
        echo "Start Time: " . $row['start_time'] . "\n";
        echo "End Time: " . $row['end_time'] . "\n\n";
    }
}

// Close the database connection
$db->close();