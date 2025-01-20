<?php

namespace src\utilities;

// All necessary imports
use DateTime;
use InvalidArgumentException;
use Monolog\Logger;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Class used for operations on table storing announcements in provided database
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
    private array $ALLOWED_FIELDS; // database fields
    private string $DATE_FORMAT; // format of date stored in database
    private const array REQUIRED_KEYS = [
        'db_host',
        'db_user',
        'db_password',
        'announcement_table_name',
        'allowed_fields',
        'date_format'
    ];
    private const int MAX_TITLE_LENGTH = 255;
    private const int MAX_TEXT_LENGTH = 10000;

    public function __construct(Logger $loggerInstance) {
        $this->config = require 'config.php';
        $this->validateConfig();
        $this->db_host = $this->config['Database']['db_host'];
        $this->db_username = $this->config['Database']['db_user'];
        $this->db_password = $this->config['Database']['db_password'];
        $this->table_name = $this->config['Database']['announcement_table_name'];

        $this->ALLOWED_FIELDS = $this->config['Database']['allowed_fields'];
        $this->DATE_FORMAT = $this->config['Database']['date_format'];

        $this->log = $loggerInstance;
        $this->pdo = $this->initializePDO();
    }

    /**
     * Validates config values
     * @return void
     */
    private function validateConfig(): void {
        try {
            foreach (self::REQUIRED_KEYS as $key) {
                if (empty($this->config['Database'][$key])) {
                    $this->log->error("Missing configuration key: $key");
                    throw new RuntimeException("Configuration error: Missing $key");
                }
            }
        } catch (RuntimeException $e) {
            $this->log->error("Configuration error: " . $e->getMessage());
        }
    }

    /**
     * @param string $input
     * @param int $maxLength
     * @return void
     */
    private function validateInput(string $input, int $maxLength): void {
        if (empty(trim($input))) {
            throw new InvalidArgumentException("Input cannot be empty");
        }
        if (mb_strlen($input) > $maxLength) {
            throw new InvalidArgumentException("Input exceeds maximum length of $maxLength");
        }
    }

    /**
     * @return PDO
     */
    private function initializePDO(): PDO {
        try {
            $pdo = new PDO($this->db_host, $this->db_username, $this->db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->log->info("PDO connection successful.");
            return $pdo;
        } catch (PDOException $e) {
            $this->log->error("PDO connection failed: " . $e->getMessage());
            throw new RuntimeException('PDO Connection failed');
        }
    }

    /**
     * @param $stmt
     * @param array $params
     * @return void
     */
    private function bindParams($stmt, array $params): void {
        try {
            foreach ($params as $key => $param) {
                if (!is_array($param) || count($param) !== 2) {
                    $this->log->error("Invalid parameter structure.", [
                        'key' => $key,
                        'value' => $param
                    ]);
                    throw new InvalidArgumentException("Invalid parameter structure detected.");
                }

                [$value, $type] = $param;
                $stmt->bindParam($key, $value, $type);
            }
            $this->log->debug("Parameters bound to statement.", ['parameters' => $params]);
        } catch (PDOException $e) {
            $this->log->error("Binding parameters to statement failed: " . $e->getMessage(), ['parameters' => $params]);
            throw new RuntimeException('Binding parameters to statement failed');
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
            $this->log->info("SQL query executed successfully.", [
                'query' => $query,
                'parameters' => $params
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log->error("SQL query execution failed: " . $e->getMessage(), [
                'query' => $query,
                'parameters' => $params
            ]);
            throw new RuntimeException('Database operation failed');
        }
    }

    /**
     * Checks if given string is a valid date format
     * @param string $date Date to be checked
     * @return bool
     */
    private function validateDate(string $date): bool {
        try {
            $d = DateTime::createFromFormat($this->DATE_FORMAT, $date);
            $isValid = $d && $d->format($this->DATE_FORMAT) === $date;

            $this->log->debug("Date format validation", [
                'date' => $date,
                'isValid' => $isValid
            ]);

            return $isValid;
        } catch (InvalidArgumentException $e) {
            $this->log->error("Invalid date format provided.". $e->getMessage());
            throw new InvalidArgumentException('Invalid date format');
        }
    }

    /**
     * Fetches all entries from provided announcements table
     * @return array All entries of table storing announcements
     */
    public function getAnnouncements(): array {
        try {
            $query = "SELECT * FROM $this->table_name";
            $this->log->info("Fetching all announcements.");
            return $this->executeStatement($query);
        } catch (PDOException $e) {
            $this->log->error("Error fetching announcements: " . $e->getMessage());
            throw new RuntimeException('Error fetching announcements');
        }
    }

    /**
     * Fetches all valid announcements
     * @return array
     */
    public function getValidAnnouncements(): array{
        try {$date = date('Y-m-d');
        $query = "SELECT * FROM $this->table_name WHERE valid_until >= :date";
        $params = [':date' => [date('Y-m-d'), PDO::PARAM_STR]];
        $this->log->info("Fetching valid announcements for date: $date.");
        return $this->executeStatement($query, $params);
        } catch (PDOException $e) {
            $this->log->error("Error fetching valid announcements: " . $e->getMessage());
            throw new RuntimeException('Error fetching valid announcements');
        }
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
        try {
            $this->validateInput($title, self::MAX_TITLE_LENGTH);
            $this->validateInput($text, self::MAX_TEXT_LENGTH);
            if (!$this->validateDate($validUntil)) {
                $this->log->error("Invalid date format provided for validUntil.", ['validUntil' => $validUntil]);
                throw new InvalidArgumentException('Invalid date format');
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
            $this->log->info("Added new announcement.", [
                'title' => $title,
                'userId' => $userId
            ]);

            return true;
        } catch (PDOException $e) {
            $this->log->error("Error adding new announcement: " . $e->getMessage());
            throw new RuntimeException('Error adding new announcement');
        }
    }


    /**
     * @param int $announcementId
     * @param string $field
     * @param string $newValue
     * @param int $userId
     * @return bool
     */
    public function updateAnnouncementField(int $announcementId, string $field, string $newValue, int $userId): bool {
        if (!in_array($field, $this->ALLOWED_FIELDS, true)) {
            $this->log->warning("Invalid field attempted for update.", [
                'field' => $field
            ]);
            throw new InvalidArgumentException("Invalid field to update: $field");
        }

        try {
            $query = "UPDATE $this->table_name SET $field = :value WHERE id = :announcementId";
            $params = [
                ':value' => [$newValue, PDO::PARAM_STR],
                ':announcementId' => [$announcementId, PDO::PARAM_INT],
            ];

            $this->executeStatement($query, $params);
            $this->log->info("Announcement updated.", [
                'announcementId' => $announcementId,
                'field' => $field,
                'newValue' => $newValue,
                'userId' => $userId
            ]);
            return true;
        } catch (PDOException $e) {
            $this->log->error("Error updating announcement: " . $e->getMessage());
            throw new RuntimeException('Error updating announcement');
        }
    }

    /**
     * Deletes selected announcement
     * @param int $announcementId ID of announcements to make changes to
     * @param int $userId ID of user making a change
     * @return bool
     */
    public function deleteAnnouncement(int $announcementId, int $userId): bool {
        try {
            $query = "DELETE FROM $this->table_name WHERE id = :announcementId";
            $params = [
                ':announcementId' => [$announcementId, PDO::PARAM_INT],
            ];

            $this->executeStatement($query, $params);
            $this->log->info("Announcement deleted.", [
                'announcementId' => $announcementId,
                'userId' => $userId
            ]);

            return true;
        } catch (PDOException $e) {
            $this->log->error("Error deleting announcement: " . $e->getMessage());
            throw new RuntimeException('Error deleting announcement');
        }
    }


    /**
     * Fetches announcements by id
     * @param int $announcementId ID of announcements to search
     * @return array Returns found announcements
     */
    public function getAnnouncementById(int $announcementId): array {
        try {
            $query = "SELECT * FROM $this->table_name WHERE id = :announcementId";
            $params = [
                ':announcementId' => [$announcementId, PDO::PARAM_INT],
            ];

            $result = $this->executeStatement($query, $params);

            if (!$result) {
                $this->log->warning("No announcement found with ID: $announcementId");
                return [];
            }

            $this->log->info("Announcement fetched successfully.", ['result' => $result]);
            return $result;
        } catch (PDOException $e) {
            $this->log->error("Error fetching announcement by ID: " . $e->getMessage());
            throw new RuntimeException('Error fetching data from the database');
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
            throw new RuntimeException('Error fetching data from the database');

        }
    }
}