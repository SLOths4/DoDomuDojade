<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Announcement\Announcement;
use App\Domain\Announcement\AnnouncementId;
use App\Domain\Announcement\AnnouncementRepositoryInterface;
use App\Domain\Announcement\AnnouncementStatus;
use App\Infrastructure\Database\DatabaseService;
use DateTimeImmutable;
use Exception;
use PDO;

/**
 * @inheritDoc
 */
readonly class PDOAnnouncementRepository implements AnnouncementRepositoryInterface
{
    public function __construct(
        private DatabaseService $dbHelper,
        private string          $TABLE_NAME,
        private string          $DATE_FORMAT,
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
     * @inheritDoc
     */
    public function findAll(): array
    {
        $rows = $this->dbHelper->getAll("SELECT * FROM $this->TABLE_NAME");
        return array_map(fn($row) => $this->mapRow($row), $rows);
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function add(Announcement $announcement): AnnouncementId
    {
        $lastId = $this->dbHelper->insert(
            $this->TABLE_NAME,
            [
                'id'          => [$announcement->getId(), PDO::PARAM_STR],
                'title'       => [$announcement->title, PDO::PARAM_STR],
                'text'        => [$announcement->text, PDO::PARAM_STR],
                'date'        => [$announcement->getCreatedAt()->format($this->DATE_FORMAT), PDO::PARAM_STR],
                'valid_until' => [$announcement->validUntil->format($this->DATE_FORMAT), PDO::PARAM_STR],
                'status'      => [$announcement->status->name, PDO::PARAM_STR],
                'user_id'     => [$announcement->getUserId(), PDO::PARAM_INT],
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

        return new AnnouncementId($lastId);
    }

    /**
     * @inheritDoc
     */
    public function update(Announcement $announcement): int
    {
        return $this->dbHelper->update(
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
                'id' => [$announcement->getId(), PDO::PARAM_STR],
            ]
        );
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
