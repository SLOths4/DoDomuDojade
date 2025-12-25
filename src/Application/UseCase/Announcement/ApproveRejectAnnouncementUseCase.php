<?php

declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\Domain\Enum\AnnouncementStatus;
use App\Domain\Exception\AnnouncementException;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Repository\AnnouncementRepository;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

readonly class ApproveRejectAnnouncementUseCase
{
    public function __construct(
        private AnnouncementRepository $repository,
        private LoggerInterface $logger,
        private AnnouncementValidationHelper $validator,
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

        $this->logger->info('Announcement decision made', [
            'announcement_id' => $announcementId,
            'status' => $status->name,
        ]);
    }
}
