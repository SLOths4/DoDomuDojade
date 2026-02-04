<?php

namespace App\Presentation\Http\Presenter;

use App\Domain\Announcement\Announcement;
use App\Presentation\Http\DTO\AnnouncementApiDTO;
use App\Presentation\Http\DTO\AnnouncementViewDTO;

final class AnnouncementPresenter
{
    /** @return AnnouncementApiDTO[] */
    public function toApi(array $announcements): array
    {
        return array_map(
            fn (Announcement $a) => new AnnouncementApiDTO(
                id: (string) $a->getId(),
                title: $a->title,
                text: $a->text,
                status: $a->status->value,
                authorId: $a->getUserId(),
                createdAt: $a->getCreatedAt()->format(DATE_ATOM),
                validUntil: $a->validUntil->format(DATE_ATOM),
                decidedAt: $a->decidedAt?->format(DATE_ATOM),
                decidedBy: $a->decidedBy,
            ),
            $announcements
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
                createdAt: $a->getCreatedAt()->format('Y-m-d H:i'),
                validUntil: $a->validUntil->format('Y-m-d'),
                decidedAt: $a->decidedAt?->format('Y-m-d H:i'),
                authorUsername: $a->getUserId() !== null
                    ? ($usernames[$a->getUserId()] ?? null)
                    : null,
                decidedByName: $a->decidedBy !== null
                    ? ($usernames[$a->decidedBy] ?? null)
                    : null,
            ),
            $announcements
        );
    }
}
