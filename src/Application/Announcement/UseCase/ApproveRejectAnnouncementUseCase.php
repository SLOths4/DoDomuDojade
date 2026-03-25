<?php

declare(strict_types=1);

namespace App\Application\Announcement\UseCase;

use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementId;
use App\Domain\Announcement\AnnouncementStatus;
use App\Domain\Event\EventPublisher;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Domain\Announcement\AnnouncementRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Use case for approving announcements
 */
readonly class ApproveRejectAnnouncementUseCase
{
    /**
     * @param AnnouncementRepositoryInterface $repository
     * @param LoggerInterface $logger
     * @param AnnouncementBusinessValidator $validator
     */
    public function __construct(
        private EventPublisher               $eventPublisher,
        private AnnouncementRepositoryInterface    $repository,
        private LoggerInterface              $logger,
        private AnnouncementBusinessValidator $validator,
    ) {}

    /**
     * @param AnnouncementId $announcementId
     * @param AnnouncementStatus $status
     * @param int $adminId
     * @return void
     * @throws AnnouncementException
     */
    public function execute(AnnouncementId $announcementId, AnnouncementStatus $status, int $adminId): void
    {
        $this->logger->info('Executing ApproveRejectAnnouncementUseCase', [
            'announcement_id' => $announcementId,
            'new_status' => $status->name,
            'admin_id' => $adminId
        ]);

        $this->validator->validateId($announcementId);

        $announcement = $this->repository->findById($announcementId);

        if (!$announcement) {
            throw AnnouncementException::notFound($announcementId);
        }

        if ($status === AnnouncementStatus::APPROVED) {
            $announcement->approve($adminId);
        } else {
            $announcement->reject($adminId);
        }


        $result = $this->repository->update($announcement);

        if (!$result) {
            throw AnnouncementException::failedToUpdateStatus();
        }

        $events = $announcement->getDomainEvents();
        $this->eventPublisher->publishAll($events);

        $announcement->clearDomainEvents();

        $this->logger->info('Announcement decision made', [
            'announcement_id' => $announcementId,
            'status' => $status->name,
        ]);
    }
}
