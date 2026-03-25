<?php
declare(strict_types=1);

namespace App\Application\Announcement\UseCase;

use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementId;
use App\Domain\Event\EventPublisher;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Persistence\PDOAnnouncementRepository;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Use case for deleting announcements
 */
readonly class DeleteAnnouncementUseCase
{
    /**
     * @param PDOAnnouncementRepository $repository
     * @param LoggerInterface $logger
     * @param AnnouncementValidationHelper $validator
     */
    public function __construct(
        private PDOAnnouncementRepository    $repository,
        private EventPublisher               $eventPublisher,
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

        $announcement = $this->repository->findById($announcementId);
        if ($announcement === null) {
            throw AnnouncementException::notFound($announcementId);
        }

        $result = $this->repository->delete($announcementId);

        if (!$result) {
            throw AnnouncementException::failedToDelete($announcementId);
        }

        $announcement->markDeleted();
        $events = $announcement->getDomainEvents();
        $this->eventPublisher->publishAll($events);
        $announcement->clearDomainEvents();

        $this->logger->info('Announcement deleted successfully', [
            'announcement_id' => $announcementId,
            'success' => true
        ]);

        return true;
    }
}
