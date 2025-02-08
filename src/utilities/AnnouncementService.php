<?php

namespace src\utilities;

use DateTime;
use InvalidArgumentException;
use Monolog\Logger;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Class used for operations on table storing announcements in provided database
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 */
class AnnouncementService{
    // Database structure: id | title | text | date (posted on) | valid_until | user_id (user making changes)
    private PDO $pdo; // PDO instance
    private Logger $logger; // Monolog logger instance
    private string $table_name; // announcements table name
    private string $DATE_FORMAT;
    private int $MAX_TITLE_LENGTH;
    private int $MAX_TEXT_LENGTH;
    private array $ALLOWED_FIELDS;

    /**
     * Konstruktor klasy AnnouncementService
     *
     * @param Logger $loggerInstance
     * @param PDO $pdoInstance
     * @param string $table_name
     * @param string $date_format
     * @param int $max_title_length
     * @param int $max_text_length
     * @param array $allowed_fields
     */
    public function __construct(Logger $loggerInstance, PDO $pdoInstance, string $table_name = 'announcements', string $date_format = 'Y-m-d', int $max_title_length = 255, int $max_text_length = 10000, array $allowed_fields = ['title', 'text', 'date','valid_until', 'user_id']) {
        $this->logger = $loggerInstance;
        $this->pdo = $pdoInstance;
        // settings
        $this->table_name = $table_name;
        $this->DATE_FORMAT = $date_format;
        $this->MAX_TITLE_LENGTH = $max_title_length;
        $this->MAX_TEXT_LENGTH = $max_text_length;
        $this->ALLOWED_FIELDS = $allowed_fields;

        $this->logger->debug("Announcements table name being used: $this->table_name");
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
     * @param PDOStatement $stmt
     * @param array $params
     * @return void
     */
    private function bindParams(PDOStatement $stmt, array $params): void {
        foreach ($params as $key => $param) {
            if (!is_array($param) || count($param) !== 2) {
                $this->logger->error("Invalid parameter structure.", [
                    'key' => $key,
                    'param' => $param
                ]);
                throw new InvalidArgumentException("Invalid parameter structure for key $key.");
            }

            [$value, $type] = $param;

            $this->logger->debug("Binding parameter:", [
                'key' => $key,
                'value' => $value,
                'type' => $type
            ]);

            try {
                $stmt->bindValue($key, $value, $type);
            } catch (PDOException $e) {
                $this->logger->error("Failed to bind parameter to statement.", [
                    'key' => $key,
                    'value' => $value,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
                throw new RuntimeException("Failed to bind parameter: $key");
            }
        }

        $this->logger->debug("All parameters successfully bound.", ['parameters' => $params]);
    }


    /**
     * @param string $query
     * @param array $params
     * @return array
     */
    private function executeStatement(string $query, array $params = []): array {
        try {
            $stmt = $this->pdo->prepare($query);
            $this->logger->debug("Executing query:", ['query' => $query]);
            if (!empty($params)) {
                $this->bindParams($stmt, $params);
            }
            $start = microtime(true);
            $stmt->execute();
            $executionTime = round((microtime(true) - $start) * 1000, 2);
            $this->logger->info("SQL query executed successfully.", [
                'query' => $query,
                'execution_time_ms' => $executionTime,
                'parameters' => $params
            ]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($results)) {
                $this->logger->warning("SQL query executed but returned no results.", [
                    'query' => $query
                ]);
            }
            return $results;
        } catch (PDOException $e) {
            $this->logger->error("SQL query execution failed: " . $e->getMessage(), [
                'query' => $query,
                'parameters' => $params
            ]);
            throw new RuntimeException('Database operation failed');
        }
    }

    /**
     * @param string $date
     * @return bool
     */
    private function validateDate(string $date): bool {
        try {
            $d = DateTime::createFromFormat($this->DATE_FORMAT, $date);
            $isValid = $d && $d->format($this->DATE_FORMAT) === $date;

            $this->logger->debug("Date format validation", [
                'date' => $date,
                'isValid' => $isValid
            ]);

            return $isValid;
        } catch (InvalidArgumentException $e) {
            $this->logger->error("Invalid date format provided.". $e->getMessage());
            throw new InvalidArgumentException('Invalid date format');
        }
    }

    /**
     * Fetches all entries from provided announcements table
     * @return array
     */
    public function getAnnouncements(): array {
        try {
            $query = "SELECT * FROM $this->table_name";
            $this->logger->info("Fetching all announcements.");
            return $this->executeStatement($query);
        } catch (PDOException $e) {
            $this->logger->error("Error fetching announcements: " . $e->getMessage());
            throw new RuntimeException('Error fetching announcements');
        }
    }

    /**
     * Fetches all valid announcements
     * @return array
     */
    public function getValidAnnouncements(): array{
        try {
            $date = date('Y-m-d');
            $query = "SELECT * FROM $this->table_name WHERE valid_until >= :date";
            $params = [':date' => [$date, PDO::PARAM_STR]];
            $this->logger->info("Fetching valid announcements for date: $date.");
            $result = $this->executeStatement($query, $params);
            if (!$result) {
                $this->logger->warning("No valid announcements found for date: $date");
                return [];
            }
            $this->logger->info("Valid announcements fetched successfully.", ['result' => $result]);
            return $result;
        } catch (PDOException $e) {
            $this->logger->error("Error fetching valid announcements: " . $e->getMessage());
            throw new RuntimeException('Error fetching valid announcements');
        }
    }

    /**
     * Adds new announcement
     * @param string $title
     * @param string $text
     * @param string $validUntil
     * @param int $userId
     * @return bool
     */
    public function addAnnouncement(string $title, string $text, string $validUntil, int $userId): bool {
        $this->logger->debug("Received values:", [
            'title' => $title,
            'text' => $text,
            'validUntil' => $validUntil,
            'userId' => $userId
        ]);

        try {
            $this->validateInput($title, $this->MAX_TITLE_LENGTH);
            $this->validateInput($text, $this->MAX_TEXT_LENGTH);
            if (!$this->validateDate($validUntil)) {
                $this->logger->error("Invalid date format provided for validUntil.", ['validUntil' => $validUntil]);
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
            $this->logger->info("Added new announcement.", [
                'title' => $title,
                'userId' => $userId
            ]);

            return true;
        } catch (PDOException $e) {
            $this->logger->error("Error adding new announcement: " . $e->getMessage());
            throw new RuntimeException('Error adding new announcement');
        }
    }

    /**
     * Updated chosen filed of announcement with given value
     * @param int $announcementId
     * @param string $field
     * @param string $newValue
     * @param int $userId
     * @return bool
     */
    public function updateAnnouncementField(int $announcementId, string $field, string $newValue, int $userId): bool {
        if (!in_array($field, $this->ALLOWED_FIELDS, true)) {
            $this->logger->warning("Invalid field attempted for update.", [
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
            $this->logger->info("Announcement updated.", [
                'announcementId' => $announcementId,
                'field' => $field,
                'newValue' => $newValue,
                'userId' => $userId
            ]);
            return true;
        } catch (PDOException $e) {
            $this->logger->error("Error updating announcement: " . $e->getMessage());
            throw new RuntimeException('Error updating announcement');
        }
    }

    /**
     * Deletes selected announcement
     * @param int $announcementId
     * @param int $userId
     * @return bool
     */
    public function deleteAnnouncement(int $announcementId, int $userId): bool {
        try {
            $query = "DELETE FROM $this->table_name WHERE id = :announcementId";
            $params = [
                ':announcementId' => [$announcementId, PDO::PARAM_INT],
            ];

            $this->executeStatement($query, $params);
            $this->logger->info("Announcement deleted.", [
                'announcementId' => $announcementId,
                'userId' => $userId
            ]);

            return true;
        } catch (PDOException $e) {
            $this->logger->error("Error deleting announcement: " . $e->getMessage());
            throw new RuntimeException('Error deleting announcement');
        }
    }

    /**
     * Fetches announcements by id
     * @param int $announcementId
     * @return array
     */
    public function getAnnouncementById(int $announcementId): array {
        try {
            $query = "SELECT * FROM $this->table_name WHERE id = :announcementId";
            $params = [
                ':announcementId' => [$announcementId, PDO::PARAM_INT],
            ];

            $result = $this->executeStatement($query, $params);

            if (!$result) {
                $this->logger->warning("No announcement found with ID: $announcementId");
                return [];
            }

            $this->logger->info("Announcement fetched successfully.", ['result' => $result]);
            return $result;
        } catch (PDOException $e) {
            $this->logger->error("Error fetching announcement by ID: " . $e->getMessage());
            throw new RuntimeException('Error fetching data from the database');
        }
    }

    /**
     * Fetches announcements by provided title from database
     * @param string $announcementTitle
     * @return array
     */
    public function getAnnouncementByTitle(string $announcementTitle): array {
        try {
            // query structure
            $query = "SELECT * FROM $this->table_name WHERE title LIKE :announcementTitle";
            $statement = $this->pdo->prepare($query);
            $pattern = '%' . $announcementTitle . '%';
            $statement->bindParam(':announcementTitle', $pattern);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new RuntimeException('Error fetching data from the database');

        }
    }
}