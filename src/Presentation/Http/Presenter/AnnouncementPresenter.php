<?php

namespace App\Presentation\Http\Presenter;

use App\Domain\Announcement\Announcement;
use App\Presentation\Http\DTO\AnnouncementApiDTO;
use App\Presentation\Http\DTO\AnnouncementViewDTO;

final class AnnouncementPresenter
{
    /** @return AnnouncementApiDTO[] */
    public function toApi(array $announcements, array $usernames = []): array
    {
        return array_map(
            fn (Announcement $announcement) => $this->mapAnnouncementToApiDto($announcement, $usernames),
            $announcements
        );
    }

    /** @return AnnouncementViewDTO[] */
    public function toView(
        array $announcements,
        array $usernames,
    ): array {
        return array_map(
            fn (Announcement $announcement) => $this->mapAnnouncementToViewDto($announcement, $usernames),
            $announcements
        );
    }

    private function mapAnnouncementToApiDto(Announcement $announcement, array $usernames): AnnouncementApiDTO
    {
        return new AnnouncementApiDTO(
            id: (string) $announcement->getId(),
            title: $announcement->title,
            text: $announcement->text,
            status: $announcement->status->value,
            authorId: $announcement->getUserId(),
            authorUsername: $this->resolveUsername($announcement->getUserId(), $usernames),
            createdAt: $announcement->getCreatedAt()->format('Y-m-d'),
            validUntil: $announcement->validUntil->format('Y-m-d'),
            decidedAt: $announcement->decidedAt?->format('Y-m-d'),
            decidedBy: $announcement->decidedBy,
            decidedByName: $this->resolveUsername($announcement->decidedBy, $usernames),
        );
    }

    private function mapAnnouncementToViewDto(Announcement $announcement, array $usernames): AnnouncementViewDTO
    {
        return new AnnouncementViewDTO(
            id: (string) $announcement->getId(),
            title: $announcement->title,
            text: $announcement->text,
            status: $announcement->status->value,
            createdAt: $announcement->getCreatedAt()->format('Y-m-d'),
            validUntil: $announcement->validUntil->format('Y-m-d'),
            decidedAt: $announcement->decidedAt?->format('Y-m-d'),
            authorUsername: $this->resolveUsername($announcement->getUserId(), $usernames),
            decidedByName: $this->resolveUsername($announcement->decidedBy, $usernames),
        );
    }

    private function resolveUsername(?int $userId, array $usernames): ?string
    {
        if ($userId === null) {
            return null;
        }

        return $usernames[$userId] ?? null;
    }
}
