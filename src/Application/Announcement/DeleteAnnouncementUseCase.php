<?php
declare(strict_types=1);

namespace App\Application\Announcement;

use App\Domain\Announcement\AnnouncementException;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Persistence\PDOAnnouncementRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class DeleteAnnouncementUseCase
{
    public function __construct(
        private PDOAnnouncementRepository    $repository,
        private LoggerInterface              $logger,
        private AnnouncementValidationHelper $validator,
    ) {}

    /**
     * Deletes announcement
     * @param int $announcementId
     * @return bool
     * @throws Exception
     */
    public function execute(int $announcementId): bool
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
