<?php

namespace src\utilities;

use Exception;
use Monolog\Logger;
use PDO;
use PDOException;
use PDOStatement;

class CountdownService
{
    // DB structure: id | title | count_to
    private Logger $logger;
    private PDO $pdo;
    private string $CountdownTableName;

    public function __construct(Logger $loggerInstance, PDO $pdoInstance)
    {
        $this->logger = $loggerInstance;
        $this->pdo = $pdoInstance;
        $this->CountdownTableName = getenv('COUNTDOWN_TABLE_NAME') ?: 'countdowns';
        $this->logger->info('CountdownService został zainicjalizowany z nazwą tabeli: ' . $this->CountdownTableName);
    }

    /**
     * Metoda pomocnicza do wykonywania zapytań.
     *
     * @param string $query
     * @param array $params
     * @return PDOStatement
     * @throws PDOException
     */
    private function executeQuery(string $query, array $params = []): PDOStatement
    {
        try {
            $this->logger->debug('Przygotowanie zapytania SQL: ' . $query, ['parametry' => $params]);
            $statement = $this->pdo->prepare($query);
            $statement->execute($params);
            $this->logger->debug('Wykonano zapytanie SQL: ' . $query);
            return $statement;
        } catch (PDOException $e) {
            $this->logger->error('Błąd podczas wykonywania zapytania', [
                'zapytanie' => $query,
                'parametry' => $params,
                'komunikat_błędu' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getCurrentCountdown(): array
    {
        try {
            $currentTime = date('Y-m-d');
            $this->logger->info('Rozpoczęcie pobierania obecnego odliczania.', ['current_time' => $currentTime]);

            $query = "SELECT * FROM $this->CountdownTableName WHERE count_to <= :current_time LIMIT 1";

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

            $statement = $this->executeQuery("SELECT * FROM $this->CountdownTableName");
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);

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

            $query = "INSERT INTO $this->CountdownTableName (title, count_to) VALUES (:title, :count_to)";
            $this->executeQuery($query, [
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

            $query = "DELETE FROM $this->CountdownTableName WHERE id = :id";
            $this->executeQuery($query, [
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

            $query = "UPDATE $this->CountdownTableName SET title = :title, count_to = :count_to WHERE id = :id";
            $this->executeQuery($query, [
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