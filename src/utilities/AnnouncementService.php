<?php

namespace src\utilities;


use PDO;
use PDOException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class AnnouncementService{
    // Database structure: id | title | text | date (posted on) | valid_until | user_id
    private PDO $pdo;
    private $config;
    private Logger $log;
    private string $db_host;
    private string $db_type;
    private string $db_name;
    private string $db_user;
    private string $db_password;

    function __construct(){
        $this->config = require 'config.php';
        $this->log = new Logger('AnnouncementDatabaseLogger');
        $this->db_host = $this->config['Database']['db_host'];
        $this->db_type = $this->config['Database']['db_type'];
        $this->db_name = $this->config['Database']['db_name'];
        $this->db_user = $this->config['Database']['db_user'];
        $this->db_password = $this->config['Database']['db_password'];

        // Logger setup
        $this->log->pushHandler(new StreamHandler('AnnouncementDatabaseLogger.log', Logger::INFO));

        try {
            $db_full_name = "$this->db_type:host=$this->db_host;dbname=$this->db_name"; //to be added // 'mysql:host=localhost;dbname=testdb'
            $this->pdo = new PDO($db_full_name, $this->db_user, $this->db_password);
            // Set the PDO error mode to exception
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->log->info("Connected successfully");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS $this->db_name (id INTEGER NOT NULL PRIMARY KEY, title NOT NULL TEXT, text NOT NULL TEXT, date NOT NULL DATE, valid_until NOT NULL DATE, user_id INTEGER NOT NULL, PRIMARY KEY(id))");

        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
            throw new \RuntimeException('Connection failed');

        }
    }

    function __create_table() {

    }

    function getAnnouncements() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM $this->db_name");

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new \RuntimeException('Błąd w trakcie uzyskiwania danych z bazy');

        }
    }

    function addAnnouncement(string $announcementTitle, string $announcementText, $validUntil, int $userId) {
        $date = date('d.m.Y');
        $query = "INSERT INTO $this->db_name (title, text, date, valid_until, user_id) VALUES (:title, :text, :date, :valid_until, :user_id)";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':title', $announcementTitle, PDO::PARAM_STR);
        $stmt->bindParam(':text', $announcementText, PDO::PARAM_STR);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->bindParam(':valid_until', $validUntil, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        // auto numerki nie wiem, czy trzeba je jakoś aktywować

        try {
            $stmt->execute();
            $this->log->info("New database entry was created");

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new \RuntimeException('Błąd w trakcie uzyskiwania danych z bazy');

        }
    }

    function updateAnnouncementTitle(int $announcementId, string $announcementNewTitle, int $userId) {
        $query = "UPDATE $this->db_name SET title = :title WHERE id = :announcementId";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':title', $announcementNewTitle, PDO::PARAM_STR);
        $stmt->bindParam(':announcementId', $announcementId, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $this->log->info("Announcement $announcementId title was changed to $announcementNewTitle by $userId");

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new \RuntimeException('Błąd w trakcie uzyskiwania danych z bazy');

        }
    }

    function updateAnnouncementText(int $announcementId, string $announcementNewText, int $userId) {
        $query = "UPDATE $this->db_name SET text = :text WHERE id = :announcementId";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':text', $announcementNewText, PDO::PARAM_STR);
        $stmt->bindParam(':announcementId', $announcementId, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $this->log->info("Announcement $announcementId text was changed to $announcementNewText by $userId");

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new \RuntimeException('Błąd w trakcie uzyskiwania danych z bazy');

        }
    }

    function deleteAnnouncement(int $announcementId, int $user_id) {
        try {
            $query = "DELETE FROM $this->db_name WHERE id = :announcementId";
            $stmt = $this->pdo->prepare($query);

            $stmt->bindParam(':announcementId', $announcementId, PDO::PARAM_INT);
            $stmt->execute();
            $this->log->info("Database entry ". $announcementId ." was deleted by" . $user_id);

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new \RuntimeException('Błąd w trakcie uzyskiwania danych z bazy');

        }
    }

    /**
     * fetches announcements by provided id from database
     * @param int $announcementId
     * @return mixed
     */
    function getAnnouncementById(int $announcementId) {
        try {
            $query = "SELECT id FROM $this->db_name WHERE id = :announcementId";
            $stmt = $this->pdo->prepare($query);

            $stmt->bindParam(':announcementId', $announcementId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new \RuntimeException('Błąd w trakcie uzyskiwania danych z bazy');

        }
    }

    /**
     * fetches announcements by provided title from database
     * @param string $announcementTitle
     * @return mixed
     */
    function getAnnouncementByTitle(string $announcementTitle) {
        try {
            $query = "SELECT title FROM $this->db_name WHERE title = :announcementTitle";
            $stmt = $this->pdo->prepare($query);

            $stmt->bindParam(':announcementTitle', $announcementTitle, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new \RuntimeException('Błąd w trakcie uzyskiwania danych z bazy');

        }
    }
}