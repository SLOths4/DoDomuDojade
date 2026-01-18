<?php
declare(strict_types=1);

namespace App\Application\Announcement\UseCase;

use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementId;
use App\Domain\Announcement\Event\AnnouncementDeletedEvent;
use App\Domain\Event\EventPublisher;
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
        private EventPublisher               $publisher,
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

        $this->publisher->publish(new AnnouncementDeletedEvent((string)$announcementId));

        $this->logger->info('Announcement deleted successfully', [
            'announcement_id' => $announcementId,
            'success' => true
        ]);

        return true;
    }
}
