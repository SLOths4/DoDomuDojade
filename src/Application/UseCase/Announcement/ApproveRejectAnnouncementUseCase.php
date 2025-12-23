<?php

declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\Domain\Entity\Announcement;
use App\Domain\Enum\AnnouncementStatus;
use App\Infrastructure\Repository\AnnouncementRepository;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

readonly class ApproveRejectAnnouncementUseCase
{
    public function __construct(
        private AnnouncementRepository $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function execute(int $announcementId, AnnouncementStatus $status, int $adminId): void
    {
        $this->logger->info('Executing ApproveRejectAnnouncementUseCase', [
            'announcement_id' => $announcementId,
            'new_status' => $status->name,
            'admin_id' => $adminId
        ]);

        $announcement = $this->repository->findById($announcementId);

        if (!$announcement) {
            throw new InvalidArgumentException('announcement.not_found');
        }

        if ($announcement->status !== AnnouncementStatus::PENDING) {
            throw new InvalidArgumentException('announcement.already_decided');
        }

        $updated = new Announcement(
            id: $announcement->id,
            title: $announcement->title,
            text: $announcement->text,
            createdAt: $announcement->createdAt,
            validUntil: $announcement->validUntil,
            userId: $announcement->userId,
            status: $status,
            decidedAt: new DateTimeImmutable(),
            decidedBy: $adminId,
        );

        $result = $this->repository->update($updated);

        if (!$result) {
            throw new InvalidArgumentException('announcement.update_failed');
        }

        $this->logger->info('Announcement decision made', [
            'announcement_id' => $announcementId,
            'status' => $status->name,
        ]);
    }
}
