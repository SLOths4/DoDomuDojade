<?php

namespace src\models;

use src\core\Model;

use DateTime;
use Exception;
use InvalidArgumentException;
use PDO;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CountdownModel extends Model
{
    public function __construct(
        PDO $pdo,
        LoggerInterface $logger,
        private readonly string $TABLE_NAME,
        private readonly array $ALLOWED_FIELDS,
    )
    {
        parent::__construct($pdo, $logger);
        $this->logger->info('Countdowns table name in being used: ' . $this->TABLE_NAME);
    }

    /**
     * Fetches announcements by id.
     * @param int $countdownId
     * @return array
     * @throws Exception
     */
    public function getCountdownById(int $countdownId): array {
        try {
            $query = "SELECT * FROM $this->TABLE_NAME WHERE id = :countdownId";
            $params = [
                ':countdownId' => [$countdownId, PDO::PARAM_INT],
            ];

            $result = $this->executeStatement($query, $params);

            if (!$result) {
                $this->logger->warning("No countdown found with ID: $countdownId");
                return [];
            }

            $this->logger->info("Countdown fetched successfully.", ['result' => $result]);
            return $result;
        } catch (Exception $e) {
            $this->logger->error("Error fetching countdown by ID: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetches countdown which is currently in operation.
     * @return array
     * @throws Exception
     */
    public function getCurrentCountdown(): array
    {
        try {
            $currentTime = date('Y-m-d H:i:s');
            $this->logger->info('Rozpoczęcie pobierania obecnego odliczania.', ['current_time' => $currentTime]);

            $query = "SELECT * FROM $this->TABLE_NAME WHERE count_to > :current_time ORDER BY count_to LIMIT 1";

            $result  = $this->executeStatement($query, [
                ":current_time" => [$currentTime, PDO::PARAM_STR]
            ]);

            if ($result) {
                $this->logger->info('Znaleziono obecne odliczanie.', ['wynik' => $result]);
            } else {
                $this->logger->info('Nie znaleziono odliczania spełniającego kryteria.');
            }

            return $result[0] ?? [];
        } catch (Exception $e) {
            $this->logger->error('Wystąpił błąd w getCurrentCountdown: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetches all countdowns.
     * @return array
     * @throws Exception
     */
    public function getCountdowns(): array
    {
        try {
            $this->logger->info('Rozpoczęcie pobierania wszystkich odliczań.');

            $results = $this->executeStatement("SELECT * FROM $this->TABLE_NAME");

            $this->logger->info('Pobrano wszystkie odliczania.', ['liczba_odliczań' => count($results)]);
            return $results;
        } catch (Exception $e) {
            throw new RuntimeException('Error fetching countdowns' . $e);
        }
    }

    /**
     * Adds a countdown with given parameters.
     * @param string $title
     * @param string $countTo
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function addCountdown(string $title, string $countTo, int $userId): bool
    {
        try {
            $this->logger->info('Rozpoczęcie dodawania nowego odliczania.', ['title' => $title, 'count_to' => $countTo, 'userId' => $userId]);

            $dateTime = DateTime::createFromFormat('Y-m-d\TH:i' , $countTo) ?: DateTime::createFromFormat('Y-m-d H:i:s', $countTo);
            if (!$dateTime) {
                throw new InvalidArgumentException("Incorrect data format: $countTo");
            }
            $formattedDate = $dateTime->format('Y-m-d H:i:s');

            $query = "INSERT INTO $this->TABLE_NAME (title, count_to, user_id) VALUES (:title, :count_to, :user_id)";
            $this->executeStatement($query, [
                ":title" => [$title, PDO::PARAM_STR],
                ":count_to" => [$formattedDate, PDO::PARAM_STR],
                ":user_id" => [$userId, PDO::PARAM_INT],
            ]);

            $this->logger->info('Dodano nowe odliczanie.', ['title' => $title, 'count_to' => $formattedDate, 'userId' => $userId]);
            return true;
        } catch (Exception $e) {
            throw new RuntimeException('Error adding countdown' . $e);
        }
    }

    /**
     * Deletes specified countdown.
     * @param int $id
     * @return bool return success
     * @throws Exception
     */
    public function deleteCountdown(int $id): bool
    {
        try {
            $this->logger->info('Rozpoczęcie usuwania odliczania.', ['id' => $id]);

            $query = "DELETE FROM $this->TABLE_NAME WHERE id = :id";
            $this->executeStatement($query, [
                ":id" => [$id, PDO::PARAM_INT]
            ]);

            $this->logger->info('Usunięto odliczanie.', ['id' => $id]);
            return true;
        } catch (Exception $e) {
            throw new RuntimeException('Error removing countdown' . $e);
        }
    }

    /**
     * Updated chosen filed of a countdown with a given value.
     * @param int $countdownId
     * @param string $field
     * @param string $newValue
     * @param int $userId
     * @return bool return success
     * @throws Exception
     */
    public function updateCountdownField(int $countdownId, string $field, string $newValue, int $userId): bool {
        if (!in_array($field, $this->ALLOWED_FIELDS, true)) {
            $this->logger->warning("Invalid field attempted for update.", [
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
            $this->logger->info("Countdown updated.", [
                'countdownId' => $countdownId,
                'field' => $field,
                'newValue' => $newValue,
                'userId' => $userId
            ]);
            return true;
        } catch (Exception $e) {
            throw new RuntimeException('Error updating announcement' . $e);
        }
    }
}