<?php

namespace src\utilities;

// All necessary imports
use DateTime;
use PDO;
use PDOException;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class used for operations on table storing announcements in provided database
 *
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 * @version 1.0.0
 * @since 1.0.0
 */
class AnnouncementService{
    // Database structure: id | title | text | date (posted on) | valid_until | user_id (user making changes)
    private PDO $pdo; // PDO instance
    private array $config; // config.php file
    private Logger $log; // Monolog logger instance
    private string $db_host; // database host
    private string $db_username; // database username
    private string $db_password; // database password
    private string $table_name; // announcements table name
    private array $ALLOWED_FIELDS;
    private string $DATE_FORMAT;


    public function __construct() {
        $this->config = require 'config.php';
        $this->db_host = $this->config['Database']['db_host'];
        $this->db_username = $this->config['Database']['db_user'];
        $this->db_password = $this->config['Database']['db_password'];
        $this->table_name = $this->config['Database']['announcement_table_name'];

        $this->ALLOWED_FIELDS = $this->config['Database']['allowed_fields'];
        $this->DATE_FORMAT = $this->config['Database']['date_format'];

        $this->log = $this->initializeLogger();
        $this->pdo = $this->initializePDO();
    }

    /**
     * @return Logger
     */
    private function initializeLogger(): Logger {
        $logger = new Logger('AnnouncementDatabaseLogger');
        $logger->pushHandler(new StreamHandler('AnnouncementDatabaseLogger.log', Level::Info));
        return $logger;
    }

    /**
     * @return PDO
     */
    private function initializePDO(): PDO {
        try {
            $pdo = new PDO($this->db_host);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->log->info("Connected successfully");
            return $pdo;
        } catch (PDOException $e) {
            $this->log->error("Connection failed: " . $e->getMessage());
            throw new \RuntimeException('Connection failed');
        }
    }

    /**
     * @param $stmt
     * @param array $params
     * @return void
     */
    private function bindParams($stmt, array $params): void {
        foreach ($params as $key => [$value, $type]) {
            $stmt->bindParam($key, $value, $type);
        }
    }

    /**
     * Executes given statement
     * @param string $query Query to be executed
     * @param array $params Parameters
     * @return array
     */
    private function executeStatement(string $query, array $params = []): array {
        try {
            $stmt = $this->pdo->prepare($query);
            $this->bindParams($stmt, $params);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log->error("Database error: " . $e->getMessage());
            throw new \RuntimeException('Database operation failed');
        }
    }

    /**
     * Checks if given string is a valid date format
     * @param string $date Date to be checked
     * @param string $format Format of date
     * @return bool
     */
    private function isValidDateFormat(string $date, string $format = 'Y-m-d'): bool {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Fetches all entries from provided announcements table
     * @return array All entries of table storing announcements
     */
    public function getAnnouncements(): array {
        $query = "SELECT * FROM $this->table_name";
        return $this->executeStatement($query);
    }

    /**
     * Adds new announcement
     * @param string $title Title for new announcement
     * @param string $text Contents of new announcement
     * @param string $validUntil Date until which announcement is valid
     * @param int $userId ID of user creating announcement
     * @return bool
     */
    public function addAnnouncement(string $title, string $text, string $validUntil, int $userId): bool {
        if (!$this->isValidDateFormat($validUntil)) {
            $this->log->error("Invalid date format for validUntil: $validUntil");
            throw new \InvalidArgumentException('Invalid date format');
        }

        $query = "INSERT INTO $this->table_name (title, text, date, valid_until, user_id) 
                  VALUES (:title, :text, :date, :valid_until, :user_id)";
        $params = [
            ':title' => [$title, PDO::PARAM_STR],
            ':text' => [$text, PDO::PARAM_STR],
            ':date' => [date('Y-m-d'), PDO::PARAM_STR],
            ':valid_until' => [$validUntil, PDO::PARAM_STR],
            ':user_id' => [$userId, PDO::PARAM_INT],
        ];

        $this->executeStatement($query, $params);
        $this->log->info("Added new announcement: $title");
        return true;
    }


    /**
     * @param int $announcementId
     * @param string $field
     * @param string $newValue
     * @param int $userId
     * @return bool
     */
    public function updateAnnouncementField(int $announcementId, string $field, string $newValue, int $userId): bool {
        if (!in_array($field, ['title', 'text', 'valid_until'], true)) {
            throw new \InvalidArgumentException("Invalid field to update: $field");
        }

        $query = "UPDATE $this->table_name SET $field = :value WHERE id = :announcementId";
        $params = [
            ':value' => [$newValue, PDO::PARAM_STR],
            ':announcementId' => [$announcementId, PDO::PARAM_INT],
        ];

        $this->executeStatement($query, $params);
        $this->log->info("Updated announcement $announcementId: set $field to $newValue by user $userId");
        return true;
    }

    /**
     * Deletes selected announcement
     * @param int $announcementId ID of announcements to make changes to
     * @param int $userId ID of user making a change
     * @return bool
     */
    public function deleteAnnouncement(int $announcementId, int $userId): bool {
        $query = "DELETE FROM $this->table_name WHERE id = :announcementId";
        $params = [
            ':announcementId' => [$announcementId, PDO::PARAM_INT],
        ];

        $this->executeStatement($query, $params);
        $this->log->info("Deleted announcement $announcementId by user $userId");
        return true;
    }


    /**
     * Fetches announcements by provided id from database
     * @param int $announcementId ID of announcements to search
     * @return array Returns found announcements
     */
    public function getAnnouncementById(int $announcementId): array {
        // query structure
        $query = "SELECT * FROM $this->table_name WHERE id = :announcementId";
        $stmt = $this->pdo->prepare($query);
        // binding parameters
        $stmt->bindParam(':announcementId', $announcementId, PDO::PARAM_INT);

        try {
            // executing query
            $stmt->execute();
            // returns all found announcements
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new \RuntimeException('Błąd w trakcie uzyskiwania danych z bazy');

        }
    }

    /**
     * Fetches announcements by provided title from database
     * @param string $announcementTitle Title of announcements to search for
     * @return array Returns found announcements
     */
    public function getAnnouncementByTitle(string $announcementTitle): array {
        try {
            // query structure
            $query = "SELECT * FROM $this->table_name WHERE title LIKE :announcementTitle";
            $stmt = $this->pdo->prepare($query);
            // creating pattern for searching in database
            $pattern = '%' . $announcementTitle . '%';
            // binding parameters
            $stmt->bindParam(':announcementTitle', $pattern);
            // executing query
            $stmt->execute();
            // returns all found announcements
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new \RuntimeException('Błąd w trakcie uzyskiwania danych z bazy');

        }
    }
}