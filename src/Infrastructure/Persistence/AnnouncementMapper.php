<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Announcement\Announcement;
use App\Domain\Announcement\AnnouncementId;
use App\Domain\Announcement\AnnouncementStatus;
use DateTimeImmutable;
use Exception;

class AnnouncementMapper
{
    /**
     * @param array<string, mixed> $data
     * @return Announcement
     * @throws Exception
     */
    public function toEntity(array $data): Announcement
    {
        return new Announcement(
            id: $data['id'] ? new AnnouncementId($data['id']) : null,
            title: $data['title'],
            text: $data['text'],
            createdAt: new DateTimeImmutable($data['date']),
            validUntil: new DateTimeImmutable($data['valid_until']),
            userId: $data['user_id'] ? (int)$data['user_id'] : null,
            status: AnnouncementStatus::from($data['status']),
            decidedAt: $data['decided_at'] ? new DateTimeImmutable($data['decided_at']) : null,
            decidedBy: $data['decided_by'] ? (int)$data['decided_by'] : null,
        );
    }

    /**
     * @param Announcement $announcement
     * @param string $dateFormat
     * @return array<string, mixed>
     */
    public function toDatabase(Announcement $announcement, string $dateFormat): array
    {
        return [
            'id'          => $announcement->getId()?->getValue(),
            'title'       => $announcement->title,
            'text'        => $announcement->text,
            'date'        => $announcement->getCreatedAt()->format($dateFormat),
            'valid_until' => $announcement->validUntil->format($dateFormat),
            'user_id'     => $announcement->getUserId(),
            'status'      => $announcement->status->value,
            'decided_at'  => $announcement->decidedAt?->format($dateFormat),
            'decided_by'  => $announcement->decidedBy,
        ];
    }
}
