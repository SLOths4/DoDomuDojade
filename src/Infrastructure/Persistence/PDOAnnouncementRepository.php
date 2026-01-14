<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Announcement\Announcement;
use App\Domain\Announcement\AnnouncementId;
use App\Domain\Announcement\AnnouncementRepository;
use App\Domain\Announcement\AnnouncementStatus;
use App\Infrastructure\Helper\DatabaseHelper;
use DateTimeImmutable;
use Exception;
use PDO;

readonly class PDOAnnouncementRepository implements AnnouncementRepository
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
        return Announcement::fromArray($r);
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
     * @param AnnouncementId $id
     * @return Announcement|null
     * @throws Exception
     */
    public function findById(AnnouncementId $id): ?Announcement
    {
        $row = $this->dbHelper->getOne(
            "SELECT * FROM $this->TABLE_NAME WHERE id = :id",
            [':id' => [$id, PDO::PARAM_STR]]
        );

        return $row ? $this->mapRow($row) : null;
    }

    /**
     * Adds an announcement.
     * @param Announcement $announcement
     * @return AnnouncementId
     * @throws Exception
     */
    public function add(Announcement $announcement): AnnouncementId
    {
        $lastId = $this->dbHelper->insert(
            $this->TABLE_NAME,
            [
                'id'          => [$announcement->getId(), PDO::PARAM_STR],
                'title'       => [$announcement->getTitle(), PDO::PARAM_STR],
                'text'        => [$announcement->getText(), PDO::PARAM_STR],
                'date'        => [$announcement->getCreatedAt()->format($this->DATE_FORMAT), PDO::PARAM_STR],
                'valid_until' => [$announcement->getValidUntil()->format($this->DATE_FORMAT), PDO::PARAM_STR],
                'status'      => [$announcement->getStatus()->name, PDO::PARAM_STR],
                'user_id'     => [$announcement->getUserId(), PDO::PARAM_INT],
                'decided_at'  => [
                    $announcement->getDecidedAt()?->format($this->DATE_FORMAT),
                    $announcement->getDecidedAt() === null ? PDO::PARAM_NULL : PDO::PARAM_STR
                ],
                'decided_by'  => [
                    $announcement->getDecidedBy(),
                    $announcement->getDecidedBy() === null ? PDO::PARAM_NULL : PDO::PARAM_INT
                ],
            ]
        );

        return new AnnouncementId($lastId);
    }

    /**
     * Updates an announcement.
     * @param Announcement $announcement
     * @return int
     * @throws Exception
     */
    public function update(Announcement $announcement): int
    {
        return $this->dbHelper->update(
            $this->TABLE_NAME,
            [
                'title'       => [$announcement->getTitle(), PDO::PARAM_STR],
                'text'        => [$announcement->getText(), PDO::PARAM_STR],
                'valid_until' => [$announcement->getValidUntil()->format($this->DATE_FORMAT), PDO::PARAM_STR],
                'status'      => [$announcement->getStatus()->name, PDO::PARAM_STR],
                'decided_at'  => [$announcement->getDecidedAt()?->format($this->DATE_FORMAT), PDO::PARAM_STR],
                'decided_by'  => [$announcement->getDecidedBy(), PDO::PARAM_INT],
            ],
            [
                'id' => [$announcement->getId(), PDO::PARAM_STR],
            ]
        );
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
     * @param AnnouncementId $id
     * @return int
     * @throws Exception
     */
    public function delete(AnnouncementId $id): int
    {
        return $this->dbHelper->delete(
            $this->TABLE_NAME,
            [
                'id' => [$id, PDO::PARAM_STR],
            ]
        );
    }
}
