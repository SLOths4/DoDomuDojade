<?php

namespace src\models;

use Exception;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use src\core\Model;

class CountdownModel extends Model
{
    private string $TABLE_NAME;
    private array $ALLOWED_FIELDS;

    public function __construct()
    {
        $this->TABLE_NAME = self::getConfigVariable('COUNTDOWN_TABLE_NAME') ?: 'countdowns';
        $this->ALLOWED_FIELDS = self::getConfigVariable("ALLOWED_FIELDS") ?? ['title', 'count_to'];
        self::$logger->info('Countdowns table name in being used: ' . $this->TABLE_NAME);
    }

    /**
     * Fetches announcements by id
     * @param int $countdownId
     * @return array
     */
    public function getCountdownById(int $countdownId): array {
        try {
            $query = "SELECT * FROM $this->TABLE_NAME WHERE id = :countdownId";
            $params = [
                ':countdownId' => [$countdownId, PDO::PARAM_INT],
            ];

            $result = $this->executeStatement($query, $params);

            if (!$result) {
                self::$logger->warning("No countdown found with ID: $countdownId");
                return [];
            }

            self::$logger->info("Countdown fetched successfully.", ['result' => $result]);
            return $result;
        } catch (Exception $e) {
            self::$logger->error("Error fetching countdown by ID: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetches countdown which is currently in operation
     * @return array
     */
    public function getCurrentCountdown(): array
    {
        try {
            $currentTime = time() * 1000;
            self::$logger->info('Rozpoczęcie pobierania obecnego odliczania.', ['current_time' => $currentTime]);

            $query = "SELECT * FROM $this->TABLE_NAME WHERE count_to > :current_time ORDER BY count_to ASC LIMIT 1";

            $result  = $this->executeStatement($query, [
                ":current_time" => [$currentTime, PDO::PARAM_STR]
            ]);

            if ($result) {
                self::$logger->info('Znaleziono obecne odliczanie.', ['wynik' => $result]);
            } else {
                self::$logger->info('Nie znaleziono odliczania spełniającego kryteria.');
            }

            return $result[0] ?? [];
        } catch (Exception $e) {
            self::$logger->error('Wystąpił błąd w getCurrentCountdown: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetches all countdowns
     * @return array
     */
    public function getCountdowns(): array
    {
        try {
            self::$logger->info('Rozpoczęcie pobierania wszystkich odliczań.');

            $results = $this->executeStatement("SELECT * FROM $this->TABLE_NAME");

            self::$logger->info('Pobrano wszystkie odliczania.', ['liczba_odliczań' => count($results)]);
            return $results;
        } catch (Exception $e) {
            self::$logger->error('Wystąpił błąd w getCountdowns: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Adds a countdown with given parameters
     * @param string $title
     * @param string $countTo
     * @return bool
     */
    public function addCountdown(string $title, string $countTo): bool
    {
        try {
            self::$logger->info('Rozpoczęcie dodawania nowego odliczania.', ['title' => $title, 'count_to' => $countTo]);

            $query = "INSERT INTO $this->TABLE_NAME (title, count_to) VALUES (:title, :count_to)";
            $this->executeStatement($query, [
                ":title" => [$title, PDO::PARAM_STR],
                ":count_to" => [$countTo, PDO::PARAM_STR]
            ]);

            self::$logger->info('Dodano nowe odliczanie.', ['title' => $title, 'count_to' => $countTo]);
            return true;
        } catch (Exception $e) {
            self::$logger->error('Wystąpił błąd w addCountdown: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes countdown
     * @param int $id
     * @return bool
     */
    public function deleteCountdown(int $id): bool
    {
        try {
            self::$logger->info('Rozpoczęcie usuwania odliczania.', ['id' => $id]);

            $query = "DELETE FROM $this->TABLE_NAME WHERE id = :id";
            $this->executeStatement($query, [
                ":id" => [$id, PDO::PARAM_INT]
            ]);

            self::$logger->info('Usunięto odliczanie.', ['id' => $id]);
            return true;
        } catch (Exception $e) {
            self::$logger->error('Wystąpił błąd w removeCountdown: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Updated chosen filed of a countdown with given value
     * @param int $countdownId
     * @param string $field
     * @param string $newValue
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function updateCountdownField(int $countdownId, string $field, string $newValue, int $userId): bool {
        if (!in_array($field, $this->ALLOWED_FIELDS, true)) {
            self::$logger->warning("Invalid field attempted for update.", [
                'field' => $field
            ]);
            throw new InvalidArgumentException("Invalid field to update: $field");
        }

        try {
            $query = "UPDATE $this->TABLE_NAME SET $field = :value WHERE id = :countdownId";
            $params = [
                ':value' => [$newValue, PDO::PARAM_STR],
                ':countdownId' => [$countdownId, PDO::PARAM_INT],
            ];

            $this->executeStatement($query, $params);
            self::$logger->info("Countdown updated.", [
                'countdownId' => $countdownId,
                'field' => $field,
                'newValue' => $newValue,
                'userId' => $userId
            ]);
            return true;
        } catch (Exception $e) {
            self::$logger->error("Error updating announcement: " . $e->getMessage());
            throw new RuntimeException('Error updating announcement');
        }
    }
}