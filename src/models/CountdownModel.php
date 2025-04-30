<?php

namespace src\models;

use Exception;
use PDO;
use src\core\Model;

class CountdownModel extends Model
{
    // DB structure: id | title | count_to
    private string $TABLE_NAME;

    public function __construct()
    {
        $this->TABLE_NAME = self::getConfigVariable('COUNTDOWN_TABLE_NAME') ?: 'countdowns';
        self::$logger->info('Countdowns table name in being used: ' . $this->TABLE_NAME);
    }

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

    public function addCountdown(string $title, string $count_to): bool
    {
        try {
            self::$logger->info('Rozpoczęcie dodawania nowego odliczania.', ['title' => $title, 'count_to' => $count_to]);

            $query = "INSERT INTO $this->TABLE_NAME (title, count_to) VALUES (:title, :count_to)";
            $this->executeStatement($query, [
                ":title" => [$title, PDO::PARAM_STR],
                ":count_to" => [$count_to, PDO::PARAM_STR]
            ]);

            self::$logger->info('Dodano nowe odliczanie.', ['title' => $title, 'count_to' => $count_to]);
            return true;
        } catch (Exception $e) {
            self::$logger->error('Wystąpił błąd w addCountdown: ' . $e->getMessage());
            return false;
        }
    }

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

    public function updateCountdown(int $id, string $title, string $count_to): bool
    {
        try {
            self::$logger->info('Rozpoczęcie aktualizowania odliczania.', ['id' => $id, 'title' => $title, 'count_to' => $count_to]);

            $query = "UPDATE $this->TABLE_NAME SET title = :title, count_to = :count_to WHERE id = :id";
            $this->executeStatement($query, [
                ":title" => [$title, PDO::PARAM_STR],
                ":count_to" => [$count_to, PDO::PARAM_STR],
                ":id" => [$id, PDO::PARAM_INT]
            ]);

            self::$logger->info('Zaktualizowano odliczanie.', ['id' => $id, 'title' => $title, 'count_to' => $count_to]);
            return true;
        } catch (Exception $e) {
            self::$logger->error('Wystąpił błąd w updateCountdown: ' . $e->getMessage());
            return false;
        }
    }
}