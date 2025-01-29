<?php

namespace src\utilities;

// All necessary imports
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
 * @version 1.0.1
 * @since 1.0.0
 */
class AnnouncementService{
    // Database structure: id | title | text | date (posted on) | valid_until | user_id (user making changes)
    private PDO $pdo; // PDO instance
    private Logger $logger; // Monolog logger instance
    private string $table_name; // announcements table name
    private const string DATE_FORMAT = 'Y-m-d';
    private const array ALLOWED_FIELDS = ['title', 'text', 'date','valid_until', 'user_id'];
    private const int MAX_TITLE_LENGTH = 255;
    private const int MAX_TEXT_LENGTH = 10000;

    public function __construct(Logger $loggerInstance, PDO $pdoInstance, string $table_name = 'announcements') {
        $this->table_name = $table_name;
        $this->logger = $loggerInstance;
        $this->pdo = $pdoInstance;

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
     * Executes given statement
     * @param string $query Query to be executed
     * @param array $params Parameters
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
     * Checks if given string is a valid date format
     * @param string $date Date to be checked
     * @return bool
     */
    private function validateDate(string $date): bool {
        try {
            $d = DateTime::createFromFormat(self::DATE_FORMAT, $date);
            $isValid = $d && $d->format(self::DATE_FORMAT) === $date;

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
     * @return array All entries of table storing announcements
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
        try {$date = date('Y-m-d');
        $query = "SELECT * FROM $this->table_name WHERE valid_until >= :date";
        $params = [':date' => [date('Y-m-d'), PDO::PARAM_STR]];
        $this->logger->info("Fetching valid announcements for date: $date.");
        return $this->executeStatement($query, $params);
        } catch (PDOException $e) {
            $this->logger->error("Error fetching valid announcements: " . $e->getMessage());
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
        $this->logger->debug("Received values:", [
            'title' => $title,
            'text' => $text,
            'validUntil' => $validUntil,
            'userId' => $userId
        ]);

        try {
            $this->validateInput($title, self::MAX_TITLE_LENGTH);
            $this->validateInput($text, self::MAX_TEXT_LENGTH);
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


            /*
            $date = date('Y-m-d');
            $query = "INSERT INTO $this->table_name (title, text, date, valid_until, user_id) 
                      VALUES ('$title', '$text', '$date', '$validUntil', $userId)";

            $this->executeStatement($query); //, $params);

            */
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
     * @param int $announcementId
     * @param string $field
     * @param string $newValue
     * @param int $userId
     * @return bool
     */
    public function updateAnnouncementField(int $announcementId, string $field, string $newValue, int $userId): bool {
        if (!in_array($field, self::ALLOWED_FIELDS, true)) {
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