<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Announcement;
use App\Domain\Enum\AnnouncementStatus;
use App\Infrastructure\Helper\DatabaseHelper;
use DateTimeImmutable;
use Exception;
use PDO;

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
            id: (int)$r['id'],
            title: (string)$r['title'],
            text: (string)$r['text'],
            createdAt: new DateTimeImmutable($r['date']),
            validUntil: new DateTimeImmutable($r['valid_until']),
            userId: !empty($r['user_id']) ? (int)$r['user_id'] : null,
            status: AnnouncementStatus::from($r['status']),
            decidedAt: !empty($r['decided_at']) ? new DateTimeImmutable($r['decided_at']) : null,
            decidedBy: !empty($r['decided_by']) ? (int)$r['decided_by'] : null,
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
            "SELECT * FROM $this->TABLE_NAME WHERE valid_until >= :date AND status = :status",
            [
                ':date' => [date($this->DATE_FORMAT), PDO::PARAM_STR],
                ':status' => [AnnouncementStatus::APPROVED->value, PDO::PARAM_INT]
            ]
        );
        return array_map(fn($row) => $this->mapRow($row), $rows);
    }

    /**
     * Returns pending announcements (awaiting approval).
     * @return Announcement[]
     * @throws Exception
     */
    public function findPending(): array
    {
        $rows = $this->dbHelper->getAll(
            "SELECT * FROM $this->TABLE_NAME WHERE status = :status ORDER BY date DESC",
            [':status' => [AnnouncementStatus::PENDING->value, PDO::PARAM_INT]]
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
     * @return Announcement|null
     * @throws Exception
     */
    public function findById(int $id): ?Announcement
    {
        $row = $this->dbHelper->getOne(
            "SELECT * FROM $this->TABLE_NAME WHERE id = :id",
            [':id' => [$id, PDO::PARAM_INT]]
        );

        return $row ? $this->mapRow($row) : null;
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
                'date'        => [$announcement->createdAt->format($this->DATE_FORMAT), PDO::PARAM_STR],
                'valid_until' => [$announcement->validUntil->format($this->DATE_FORMAT), PDO::PARAM_STR],
                'status'      => [$announcement->status->name, PDO::PARAM_STR],
                'user_id'     => [$announcement->userId, PDO::PARAM_INT],
                'decided_at'  => [
                    $announcement->decidedAt?->format($this->DATE_FORMAT),
                    $announcement->decidedAt === null ? PDO::PARAM_NULL : PDO::PARAM_STR
                ],
                'decided_by'  => [
                    $announcement->decidedBy,
                    $announcement->decidedBy === null ? PDO::PARAM_NULL : PDO::PARAM_INT
                ],
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
                'status'      => [$announcement->status->name, PDO::PARAM_STR],
                'decided_at'  => [$announcement->decidedAt?->format($this->DATE_FORMAT), PDO::PARAM_STR],
                'decided_by'  => [$announcement->decidedBy, PDO::PARAM_INT],
            ],
            [
                'id' => [$announcement->id, PDO::PARAM_INT],
            ]
        );

        return $affected > 0;
    }

    /**
     * Deletes rejected announcements older than the specified date.
     * @param DateTimeImmutable $date
     * @return int Number of deleted rows
     * @throws Exception
     */
    public function deleteRejectedOlderThan(DateTimeImmutable $date): int
    {
        return $this->dbHelper->delete(
            $this->TABLE_NAME,
            [
                'status' => [AnnouncementStatus::REJECTED->value, PDO::PARAM_INT],
            ],
            "decided_at < :date",
            [':date' => [$date->format($this->DATE_FORMAT), PDO::PARAM_STR]]
        );
    }

    /**
     * Deletes an announcement by ID.
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
