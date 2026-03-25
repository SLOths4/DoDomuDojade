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
            fn (Announcement $a) => new AnnouncementApiDTO(
                id: (string) $a->getId(),
                title: $a->title,
                text: $a->text,
                status: $a->status->value,
                authorId: $a->getUserId(),
                authorUsername: $a->getUserId() !== null
                    ? ($usernames[$a->getUserId()] ?? null)
                    : null,
                createdAt: $a->getCreatedAt()->format('Y-m-d'),
                validUntil: $a->validUntil->format('Y-m-d'),
                decidedAt: $a->decidedAt?->format('Y-m-d'),
                decidedBy: $a->decidedBy,
                decidedByName: $a->decidedBy !== null
                    ? ($usernames[$a->decidedBy] ?? null)
                    : null,
            ),
            $announcements,
        );
    }

    /** @return AnnouncementViewDTO[] */
    public function toView(
        array $announcements,
        array $usernames,
    ): array {
        return array_map(
            fn (Announcement $a) => new AnnouncementViewDTO(
                id: (string) $a->getId(),
                title: $a->title,
                text: $a->text,
                status: $a->status->value,
                createdAt: $a->getCreatedAt()->format('Y-m-d'),
                validUntil: $a->validUntil->format('Y-m-d'),
                decidedAt: $a->decidedAt?->format('Y-m-d'),
                authorUsername: $a->getUserId() !== null
                    ? ($usernames[$a->getUserId()] ?? null)
                    : null,
                decidedByName: $a->decidedBy !== null
                    ? ($usernames[$a->decidedBy] ?? null)
                    : null,
            ),
            $announcements,
        );
    }
}
