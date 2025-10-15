<?php

namespace src\repository;

use DateTimeImmutable;
use Exception;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;
use src\core\Model;
use src\entities\Announcement;

class AnnouncementRepository extends Model
{
    public function __construct(
        PDO $pdo,
        LoggerInterface $logger,
        private readonly string $TABLE_NAME,
        private readonly string $DATE_FORMAT,
    ) {
        parent::__construct($pdo, $logger);
    }

    /**
     * Maps database row to Announcement entity.
     * @param PDOStatement $r
     * @return Announcement
     * @throws Exception
     */
    private function mapRow(PDOStatement $r): Announcement
    {
        return new Announcement(
            (int)$r['id'],
            (string)$r['title'],
            (string)$r['text'],
            new DateTimeImmutable($r['date']),
            new DateTimeImmutable($r['valid_until']),
            (int)$r['user_id']
        );
    }

    /**
     * Returns all announcements.
     * @return Announcement[]
     * @throws Exception
     */
    public function findAll(): array
    {
        $stmt = $this->executeStatement("SELECT * FROM $this->TABLE_NAME");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => $this->mapRow($r), $rows);
    }

    /**
     * Returns all announcements that are still valid.
     * @return Announcement[]
     * @throws Exception
     */
    public function findValid(): array
    {
        $stmt = $this->executeStatement(
            "SELECT * FROM $this->TABLE_NAME WHERE valid_until >= :date",
            [':date' => [date($this->DATE_FORMAT), PDO::PARAM_STR]]
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => $this->mapRow($r), $rows);
    }

    /**
     * Returns announcements by title.
     * @param string $title
     * @return Announcement[]
     * @throws Exception
     */
    public function findByTitle(string $title): array
    {
        $stmt = $this->executeStatement(
            "SELECT * FROM $this->TABLE_NAME WHERE title LIKE :title",
            [':title' => ["%$title%", PDO::PARAM_STR]]
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => $this->mapRow($r), $rows);
    }

    /**
     * Returns a single announcement by ID.
     * @param int $id
     * @return Announcement
     * @throws Exception
     */
    public function findById(int $id): Announcement
    {
        $rows = $this->executeStatement(
            "SELECT * FROM $this->TABLE_NAME WHERE id = :id",
            [':id' => [$id, PDO::PARAM_INT]]
        );
        return $this->mapRow($rows[0]);
    }

    /**
     * Adds an announcement.
     * @param Announcement $announcement
     * @return bool
     * @throws Exception
     */
    public function add(Announcement $announcement): bool
    {
        $this->logger->debug("Adding announcement", ["announcement" => $announcement]);

        $stmt = $this->pdo->prepare("
            INSERT INTO $this->TABLE_NAME (title, text, date, valid_until, user_id)
            VALUES (:title, :text, :date, :valid_until, :user_id)
        ");

        $this->bindParams($stmt, [
            ':title' => [$announcement->title, PDO::PARAM_STR],
            ':text' => [$announcement->text, PDO::PARAM_STR],
            ':date' => [$announcement->date->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ':valid_until' => [$announcement->validUntil->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ':user_id' => [$announcement->userId, PDO::PARAM_INT],
        ]);

        $success = $stmt->execute();
        $this->logger->info("Announcement insert " . ($success ? "successful" : "failed"));

        return $success && $stmt->rowCount() > 0;
    }

    /**
     * Updates an announcement.
     * @param Announcement $announcement
     * @return bool
     * @throws Exception
     */
    public function update(Announcement $announcement): bool
    {
        $this->logger->debug("Updating announcement", ["announcement" => $announcement]);

        $stmt = $this->pdo->prepare("
            UPDATE {$this->TABLE_NAME}
            SET title = :title, text = :text, valid_until = :valid_until
            WHERE id = :id
        ");

        $this->bindParams($stmt, [
            ':id' => [$announcement->id, PDO::PARAM_INT],
            ':title' => [$announcement->title, PDO::PARAM_STR],
            ':text' => [$announcement->text, PDO::PARAM_STR],
            ':valid_until' => [$announcement->validUntil->format($this->DATE_FORMAT), PDO::PARAM_STR],
        ]);

        $success = $stmt->execute();
        $this->logger->info("Announcement update " . ($success ? "successful" : "failed"));

        return $success && $stmt->rowCount() > 0;
    }

    /**
     * Deletes an announcement.
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $this->logger->debug("Deleting announcement", ["id" => $id]);

        $stmt = $this->pdo->prepare("DELETE FROM $this->TABLE_NAME WHERE id = :id");
        $this->bindParams($stmt, [':id' => [$id, PDO::PARAM_INT]]);

        $success = $stmt->execute();
        $this->logger->info("Announcement delete " . ($success ? "successful" : "failed"));

        return $success && $stmt->rowCount() > 0;
    }
}
