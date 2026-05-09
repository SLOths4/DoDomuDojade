<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Announcement\Announcement;
use App\Domain\Announcement\AnnouncementId;
use App\Domain\Announcement\AnnouncementRepositoryException;
use App\Domain\Announcement\AnnouncementRepositoryInterface;
use App\Domain\Announcement\AnnouncementStatus;
use App\Infrastructure\Database\DatabaseException;
use App\Infrastructure\Database\DatabaseService;
use DateTimeImmutable;
use PDO;
use Throwable;

/**
 * @inheritDoc
 */
readonly class PDOAnnouncementRepository implements AnnouncementRepositoryInterface
{
    private const TABLE_NAME = 'announcement';

    public function __construct(
        private DatabaseService    $dbHelper,
        private AnnouncementMapper $mapper,
        private string             $DATE_FORMAT,
    ) {}

    /**
     * Maps database row to Announcement entity.
     * @param array $r
     * @return Announcement
     * @throws AnnouncementRepositoryException
     */
    private function mapRow(array $r): Announcement
    {
        try {
            return $this->mapper->toEntity($r);
        } catch (Throwable $e) {
            throw AnnouncementRepositoryException::fetchFailed('Failed to map announcement row', $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function findAll(): array
    {
        try {
            $rows = $this->dbHelper->getAll("SELECT * FROM " . self::TABLE_NAME);
            return array_map(fn($row) => $this->mapRow($row), $rows);
        } catch (DatabaseException $e) {
            throw AnnouncementRepositoryException::fetchFailed('Failed to fetch all announcements', $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function findValid(): array
    {
        try {
            $rows = $this->dbHelper->getAll(
                "SELECT * FROM " . self::TABLE_NAME . " WHERE valid_until >= :date AND status = :status",
                [
                    ':date' => [date($this->DATE_FORMAT), PDO::PARAM_STR],
                    ':status' => [AnnouncementStatus::APPROVED->value, PDO::PARAM_INT]
                ]
            );
            return array_map(fn($row) => $this->mapRow($row), $rows);
        } catch (DatabaseException $e) {
            throw AnnouncementRepositoryException::fetchFailed('Failed to fetch valid announcements', $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function findPending(): array
    {
        try {
            $rows = $this->dbHelper->getAll(
                "SELECT * FROM " . self::TABLE_NAME . " WHERE status = :status ORDER BY date DESC",
                [':status' => [AnnouncementStatus::PENDING->value, PDO::PARAM_INT]]
            );
            return array_map(fn($row) => $this->mapRow($row), $rows);
        } catch (DatabaseException $e) {
            throw AnnouncementRepositoryException::fetchFailed('Failed to fetch pending announcements', $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function findByTitle(string $title): array
    {
        try {
            $rows = $this->dbHelper->getAll(
                "SELECT * FROM " . self::TABLE_NAME . " WHERE title LIKE :title",
                [':title' => ["%$title%", PDO::PARAM_STR]]
            );
            return array_map(fn($row) => $this->mapRow($row), $rows);
        } catch (DatabaseException $e) {
            throw AnnouncementRepositoryException::fetchFailed('Failed to fetch announcements by title', $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function findById(AnnouncementId $id): ?Announcement
    {
        try {
            $row = $this->dbHelper->getOne(
                "SELECT * FROM " . self::TABLE_NAME . " WHERE id = :id",
                [':id' => [$id, PDO::PARAM_STR]]
            );

            return $row ? $this->mapRow($row) : null;
        } catch (DatabaseException $e) {
            throw AnnouncementRepositoryException::fetchFailed('Failed to fetch announcement by id', $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function add(Announcement $announcement): AnnouncementId
    {
        try {
            $data = $this->mapper->toDatabase($announcement, $this->DATE_FORMAT);

            $lastId = $this->dbHelper->insert(
                self::TABLE_NAME,
                [
                    'id'          => [$data['id'], PDO::PARAM_STR],
                    'title'       => [$data['title'], PDO::PARAM_STR],
                    'text'        => [$data['text'], PDO::PARAM_STR],
                    'date'        => [$data['date'], PDO::PARAM_STR],
                    'valid_until' => [$data['valid_until'], PDO::PARAM_STR],
                    'status'      => [$data['status'], PDO::PARAM_STR],
                    'user_id'     => [$data['user_id'], PDO::PARAM_INT],
                    'decided_at'  => [
                        $data['decided_at'],
                        $data['decided_at'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR
                    ],
                    'decided_by'  => [
                        $data['decided_by'],
                        $data['decided_by'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT
                    ],
                ]
            );

            return new AnnouncementId($lastId);
        } catch (DatabaseException $e) {
            throw AnnouncementRepositoryException::persistenceFailed('Failed to add announcement', $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function update(Announcement $announcement): int
    {
        try {
            $data = $this->mapper->toDatabase($announcement, $this->DATE_FORMAT);

            return $this->dbHelper->update(
                self::TABLE_NAME,
                [
                    'title'       => [$data['title'], PDO::PARAM_STR],
                    'text'        => [$data['text'], PDO::PARAM_STR],
                    'valid_until' => [$data['valid_until'], PDO::PARAM_STR],
                    'status'      => [$data['status'], PDO::PARAM_STR],
                    'decided_at'  => [$data['decided_at'], PDO::PARAM_STR],
                    'decided_by'  => [$data['decided_by'], PDO::PARAM_INT],
                ],
                [
                    'id' => [$data['id'], PDO::PARAM_STR],
                ]
            );
        } catch (DatabaseException $e) {
            throw AnnouncementRepositoryException::persistenceFailed('Failed to update announcement', $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteRejectedOlderThan(DateTimeImmutable $date): int
    {
        try {
            return $this->dbHelper->delete(
                self::TABLE_NAME,
                [
                    'status' => [AnnouncementStatus::REJECTED->value, PDO::PARAM_INT],
                ],
                "decided_at < :date",
                [':date' => [$date->format($this->DATE_FORMAT), PDO::PARAM_STR]]
            );
        } catch (DatabaseException $e) {
            throw AnnouncementRepositoryException::persistenceFailed('Failed to delete rejected announcements', $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(AnnouncementId $id): int
    {
        try {
            return $this->dbHelper->delete(
                self::TABLE_NAME,
                [
                    'id' => [$id, PDO::PARAM_STR],
                ]
            );
        } catch (DatabaseException $e) {
            throw AnnouncementRepositoryException::persistenceFailed('Failed to delete announcement', $e);
        }
    }
}
