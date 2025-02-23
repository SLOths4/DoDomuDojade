<?php

namespace src\models;

use Exception;
use Monolog\Logger;
use PDO;
use PDOException;
use PDOStatement;
use src\core\Model;

class CountdownModel extends Model
{
    // DB structure: id | title | count_to
    private Logger $logger;
    private PDO $pdo;
    private string $TABLE_NAME;

    public function __construct()
    {
        $this->logger = self::initLogger();
        $this->pdo = self::initDatabase();
        $this->TABLE_NAME = self::getConfigVariable('COUNTDOWN_TABLE_NAME') ?: 'countdowns';
        $this->logger->info('Countdowns table name in being used: ' . $this->TABLE_NAME);
    }

    public function getCurrentCountdown(): array
    {
        try {
            $currentTime = date('Y-m-d');
            $this->logger->info('Rozpoczęcie pobierania obecnego odliczania.', ['current_time' => $currentTime]);

            $query = "SELECT * FROM $this->TABLE_NAME WHERE count_to <= :current_time LIMIT 1";

            $statement = $this->executeQuery($query, [
                ":current_time" => $currentTime
            ]);

            $result = $statement->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $this->logger->info('Znaleziono obecne odliczanie.', ['wynik' => $result]);
            } else {
                $this->logger->info('Nie znaleziono odliczania spełniającego kryteria.');
            }

            return $result ?: [];
        } catch (Exception $e) {
            $this->logger->error('Wystąpił błąd w getCurrentCountdown: ' . $e->getMessage());
            return [];
        }
    }

    public function getCountdowns(): array
    {
        try {
            $this->logger->info('Rozpoczęcie pobierania wszystkich odliczań.');

            $results = $this->executeStatement("SELECT * FROM $this->TABLE_NAME");

            $this->logger->info('Pobrano wszystkie odliczania.', ['liczba_odliczań' => count($results)]);
            return $results;
        } catch (Exception $e) {
            $this->logger->error('Wystąpił błąd w getCountdowns: ' . $e->getMessage());
            return [];
        }
    }

    public function addCountdown(string $title, string $count_to): bool
    {
        try {
            $this->logger->info('Rozpoczęcie dodawania nowego odliczania.', ['title' => $title, 'count_to' => $count_to]);

            $query = "INSERT INTO $this->TABLE_NAME (title, count_to) VALUES (:title, :count_to)";
            $this->executeStatement($query, [
                ":title" => $title,
                ":count_to" => $count_to
            ]);

            $this->logger->info('Dodano nowe odliczanie.', ['title' => $title, 'count_to' => $count_to]);
            return true;
        } catch (Exception $e) {
            $this->logger->error('Wystąpił błąd w addCountdown: ' . $e->getMessage());
            return false;
        }
    }

    public function removeCountdown(int $id): bool
    {
        try {
            $this->logger->info('Rozpoczęcie usuwania odliczania.', ['id' => $id]);

            $query = "DELETE FROM $this->TABLE_NAME WHERE id = :id";
            $this->executeStatement($query, [
                ":id" => $id
            ]);

            $this->logger->info('Usunięto odliczanie.', ['id' => $id]);
            return true;
        } catch (Exception $e) {
            $this->logger->error('Wystąpił błąd w removeCountdown: ' . $e->getMessage());
            return false;
        }
    }

    public function updateCountdown(int $id, string $title, string $count_to): bool
    {
        try {
            $this->logger->info('Rozpoczęcie aktualizowania odliczania.', ['id' => $id, 'title' => $title, 'count_to' => $count_to]);

            $query = "UPDATE $this->TABLE_NAME SET title = :title, count_to = :count_to WHERE id = :id";
            $this->executeStatement($query, [
                ":title" => $title,
                ":count_to" => $count_to,
                ":id" => $id
            ]);

            $this->logger->info('Zaktualizowano odliczanie.', ['id' => $id, 'title' => $title, 'count_to' => $count_to]);
            return true;
        } catch (Exception $e) {
            $this->logger->error('Wystąpił błąd w updateCountdown: ' . $e->getMessage());
            return false;
        }
    }
}