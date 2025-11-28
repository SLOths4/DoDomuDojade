<?php

namespace App\Infrastructure\Repository;

use DateTimeImmutable;
use Exception;
use PDO;
use App\Domain\Announcement;
use App\Infrastructure\Helper\DatabaseHelper;

readonly class AnnouncementRepository
{
    public function __construct(
        private DatabaseHelper $dbHelper,
        private string  $TABLE_NAME,
        private string  $DATE_FORMAT,
    ) {}

    /**
     * Maps database row to Announcement entity.
     * @param array $r
     * @return Announcement
     * @throws Exception
     */
    private function mapRow(array $r): Announcement
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
        $rows = $this->dbHelper->getAll("SELECT * FROM $this->TABLE_NAME");
        return array_map(fn($row) => $this->mapRow($row), $rows);
    }

    /**
     * Returns all announcements that are still valid.
     * @return Announcement[]
     * @throws Exception
     */
    public function findValid(): array
    {
        $rows = $this->dbHelper->getAll(
            "SELECT * FROM $this->TABLE_NAME WHERE valid_until >= :date",
            [':date' => [date($this->DATE_FORMAT), PDO::PARAM_STR]]
        );
        return array_map(fn($row) => $this->mapRow($row), $rows);
    }

    /**
     * Returns announcements by title.
     * @param string $title
     * @return Announcement[]
     * @throws Exception
     */
    public function findByTitle(string $title): array
    {
        $rows = $this->dbHelper->getAll(
            "SELECT * FROM $this->TABLE_NAME WHERE title LIKE :title",
            [':title' => ["%$title%", PDO::PARAM_STR]]
        );
        return array_map(fn($row) => $this->mapRow($row), $rows);
    }

    /**
     * Returns a single announcement by ID.
     * @param int $id
     * @return Announcement
     * @throws Exception
     */
    public function findById(int $id): Announcement
    {
        $row = $this->dbHelper->getOne(
            "SELECT * FROM $this->TABLE_NAME WHERE id = :id",
            [':id' => [$id, PDO::PARAM_INT]]
        );

        if ($row === null) {
            throw new Exception("Announcement with ID $id not found");
        }

        return $this->mapRow($row);
    }

    /**
     * Adds an announcement.
     * @param Announcement $announcement
     * @return bool
     * @throws Exception
     */
    public function add(Announcement $announcement): bool
    {
        $lastId = $this->dbHelper->insert(
            $this->TABLE_NAME,
            [
                'title'       => [$announcement->title, PDO::PARAM_STR],
                'text'        => [$announcement->text, PDO::PARAM_STR],
                'date'        => [$announcement->date->format($this->DATE_FORMAT), PDO::PARAM_STR],
                'valid_until' => [$announcement->validUntil->format($this->DATE_FORMAT), PDO::PARAM_STR],
                'user_id'     => [$announcement->userId, PDO::PARAM_INT],
            ]
        );

        return !empty($lastId);
    }

    /**
     * Updates an announcement.
     * @param Announcement $announcement
     * @return bool
     * @throws Exception
     */
    public function update(Announcement $announcement): bool
    {
        $affected = $this->dbHelper->update(
            $this->TABLE_NAME,
            [
                'title'       => [$announcement->title, PDO::PARAM_STR],
                'text'        => [$announcement->text, PDO::PARAM_STR],
                'valid_until' => [$announcement->validUntil->format($this->DATE_FORMAT), PDO::PARAM_STR],
            ],
            [
                'id' => [$announcement->id, PDO::PARAM_INT],
            ]
        );

        return $affected > 0;
    }

    /**
     * Deletes an announcement.
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $affected = $this->dbHelper->delete(
            $this->TABLE_NAME,
            [
                'id' => [$id, PDO::PARAM_INT],
            ]
        );

        return $affected > 0;
    }
}