<?php

namespace src\models;

use DateTime;
use InvalidArgumentException;
use Monolog\Logger;
use PDO;
use PDOException;
use RuntimeException;
use src\core\Model;

/**
 * Class used for operations on table storing announcements in provided database
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 */
class AnnouncementsModel extends Model {
    // Database structure: id | title | text | date (posted on) | valid_until | user_id (user making changes)
    private Logger $logger;
    private string $TABLE_NAME;
    private string $DATE_FORMAT;
    private int $MAX_TITLE_LENGTH;
    private int $MAX_TEXT_LENGTH;
    private array $ALLOWED_FIELDS;

    public function __construct() {
        $this->logger = self::initLogger();
        // settings
        $this->TABLE_NAME = self::getConfigVariable("ANNOUNCEMENTS_TABLE_NAME") ?? 'announcements';
        $this->DATE_FORMAT = self::getConfigVariable("DATE_FORMAT") ?? 'Y-m-d';
        $this->MAX_TITLE_LENGTH = self::getConfigVariable("MAX_TITLE_LENGTH") ?? 255;
        $this->MAX_TEXT_LENGTH = self::getConfigVariable("MAX_TEXT_LENGTH") ?? 65535;
        $this->ALLOWED_FIELDS = self::getConfigVariable("ALLOWED_FIELDS") ?? ['title', 'text', 'date','valid_until', 'user_id'];

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
            $query = "SELECT * FROM $this->TABLE_NAME";
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
            $query = "SELECT * FROM $this->TABLE_NAME WHERE valid_until >= :date";
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

            $query = "INSERT INTO $this->TABLE_NAME (title, text, date, valid_until, user_id)
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
            $query = "UPDATE $this->TABLE_NAME SET $field = :value WHERE id = :announcementId";
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
            $query = "DELETE FROM $this->TABLE_NAME WHERE id = :announcementId";
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
            $query = "SELECT * FROM $this->TABLE_NAME WHERE id = :announcementId";
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
            $query = "SELECT * FROM $this->TABLE_NAME WHERE title LIKE :announcementTitle";
            $pattern = '%' . $announcementTitle . '%';
            $params = [
                ':announcementTitle' => [$pattern, PDO::PARAM_STR],
            ];

            $result = $this->executeStatement($query, $params);

            if (!$result) {
                $this->logger->warning("No announcement found with title: $announcementTitle");
                return [];
            }

            $this->logger->info("Announcement fetched successfully.", ['result' => $result]);
            return $result;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw new RuntimeException('Error fetching data from the database');
        }
    }
}