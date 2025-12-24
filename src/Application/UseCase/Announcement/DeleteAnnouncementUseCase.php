<?php
declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\Domain\Exception\AnnouncementException;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Repository\AnnouncementRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class DeleteAnnouncementUseCase
{
    public function __construct(
        private AnnouncementRepository $repository,
        private LoggerInterface $logger,
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

        $this->validator->validateAnnouncementId($announcementId);

        $result = $this->repository->delete($announcementId);

        $this->logger->info('Announcement deleted successfully', [
            'announcement_id' => $announcementId,
            'success' => $result
        ]);

        if (!$result) {
            throw AnnouncementException::failedToDelete($announcementId);
        }

        return true;
    }
}
