<?php
declare(strict_types=1);

namespace App\Application\Announcement\UseCase;

use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementId;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Domain\Announcement\AnnouncementRepositoryInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Use case for deleting announcements
 */
readonly class DeleteAnnouncementUseCase
{
    /**
     * @param AnnouncementRepositoryInterface $repository
     * @param LoggerInterface $logger
     * @param AnnouncementValidationHelper $validator
     */
    public function __construct(
        private AnnouncementRepositoryInterface    $repository,
        private LoggerInterface              $logger,
        private AnnouncementValidationHelper $validator,
    ) {}

    /**
     * Deletes announcement
     * @param AnnouncementId $announcementId
     * @return bool
     * @throws Exception
     */
    public function execute(AnnouncementId $announcementId): bool
    {
        $this->logger->info('Executing DeleteAnnouncementUseCase', ['announcement_id' => $announcementId]);

        $this->validator->validateId($announcementId);

        $result = $this->repository->delete($announcementId);

        if (!$result) {
            throw AnnouncementException::failedToDelete($announcementId);
        }

        $this->logger->info('Announcement deleted successfully', [
            'announcement_id' => $announcementId,
            'success' => true
        ]);

        return true;
    }
}
