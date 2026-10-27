<?php

namespace App\Domain\Announcement;

use DateTimeImmutable;
use Exception;

/**
 * Describes behavior of the announcement repository
 */
interface AnnouncementRepositoryInterface
{
    /**
     * Returns all announcements
     * @return Announcement[]
     * @throws AnnouncementRepositoryException
     */
    public function findAll(): array;

    /**
     * Returns all announcements that are valid on condition that
     * <li> announcement valid date is valid today</li>
     * <li> announcement is approved </li>
     * @return Announcement[]
     * @throws AnnouncementRepositoryException
     */
    public function findValid(): array;

    /**
     * Returns all announcements that have pending status
     * @return Announcement[]
     * @throws AnnouncementRepositoryException
     */
    public function findPending(): array;

    /**
     * Returns all announcement with similar title
     * @param string $title
     * @return Announcement[]
     * @throws AnnouncementRepositoryException
     */
    public function findByTitle(string $title): array;

    /**
     * Returns an announcement with provided id (if found)
     * @param AnnouncementId $id
     * @return Announcement|null
     * @throws AnnouncementRepositoryException
     */
    public function findById(AnnouncementId $id): ?Announcement;

    /**
     * Adds an announcement
     * @param Announcement $announcement
     * @return AnnouncementId
     * @throws AnnouncementRepositoryException
     */
    public function add(Announcement $announcement): AnnouncementId;

    /**
     * Updates an announcement.
     * @param Announcement $announcement
     * @return int number of affected rows
     * @throws AnnouncementRepositoryException
     */
    public function update(Announcement $announcement): int;

    /**
     * Deletes an announcement with provided ID.
     * @param AnnouncementId $id
     * @return int Number of deleted rows
     * @throws AnnouncementRepositoryException
     */
    public function delete(AnnouncementId $id): int;

    /**
     * Deletes rejected announcements older than the specified date.
     * @param DateTimeImmutable $date
     * @return int Number of deleted rows
     * @throws AnnouncementRepositoryException
     */
    public function deleteRejectedOlderThan(DateTimeImmutable $date): int;
}