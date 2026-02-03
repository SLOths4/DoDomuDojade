<?php

namespace App\Presentation\Http\Mapper;

use App\Domain\Announcement\Announcement;
use App\Domain\Announcement\AnnouncementId;
use App\Presentation\Http\DTO\AnnouncementViewDTO;
use DateTimeImmutable;

/**
 * Maps Announcement domain objects to view DTOs
 * Handles formatting and optional user data enrichment
 */
final class AnnouncementViewMapper
{
    /**
     * Map single announcement to view DTO
     */
    public function toDTO(
        Announcement $announcement,
        ?string $authorName = null,
        ?string $decidedByName = null
    ): AnnouncementViewDTO {
        $decidedBy = $announcement->decidedBy;
        $userId = $announcement->getUserId();

        return new AnnouncementViewDTO(
            id: $this->convertId($announcement->getId()),
            title: $announcement->title,
            text: $announcement->text,
            userId: $this->convertUserId($userId),
            createdAt: $this->formatDateTime($announcement->getCreatedAt()),
            validUntil: $this->formatDate($announcement->validUntil),
            status: $announcement->status->name,
            decidedAt: $announcement->decidedAt
                ? $this->formatDateTime($announcement->decidedAt)
                : null,
            decidedBy: $this->convertDecidedBy($decidedBy),
            authorName: $authorName,
            decidedByName: $decidedByName,
        );
    }

    /**
     * Map collection of announcements to view DTOs
     */
    public function toDTOCollection(
        array $announcements,
        array $usernamesMap = []
    ): array {
        return array_map(
            fn(Announcement $announcement) => $this->toDTO(
                $announcement,
                $this->getUsername($announcement->getUserId(), $usernamesMap),
                $this->getUsername($announcement->decidedBy, $usernamesMap)
            ),
            $announcements
        );
    }

    /**
     * Get username from map, handling null values safely
     */
    private function getUsername(mixed $id, array $usernamesMap): ?string
    {
        if ($id === null) {
            return null;
        }

        $key = (string)$id;
        return $usernamesMap[$key] ?? null;
    }

    /**
     * Convert AnnouncementId to string (handles null and object)
     */
    private function convertId(mixed $id): string
    {
        if ($id === null) {
            return '';
        }

        if ($id instanceof AnnouncementId) {
            return $id->getValue();
        }

        return (string)$id;
    }

    /**
     * Convert UserId or int to string (handles null)
     */
    private function convertUserId(mixed $userId): string
    {
        if ($userId === null) {
            return '';
        }

        return (string)$userId;
    }

    /**
     * Convert decidedBy to string (handles null and int)
     */
    private function convertDecidedBy(mixed $decidedBy): ?string
    {
        if ($decidedBy === null) {
            return null;
        }

        return (string)$decidedBy;
    }

    /**
     * Format DateTimeImmutable to Y-m-d H:i:s
     */
    private function formatDateTime(mixed $dateTime): string
    {
        if ($dateTime instanceof DateTimeImmutable) {
            return $dateTime->format('Y-m-d H:i:s');
        }
        return (string)$dateTime;
    }

    /**
     * Format DateTimeImmutable to Y-m-d
     */
    private function formatDate(mixed $dateTime): string
    {
        if ($dateTime instanceof DateTimeImmutable) {
            return $dateTime->format('Y-m-d');
        }
        return (string)$dateTime;
    }
}