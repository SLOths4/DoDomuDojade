<?php
declare(strict_types=1);

namespace App\Application\Announcement;

use App\Domain\Announcement\Announcement;
use App\Domain\Announcement\AnnouncementException;
use App\Domain\Event\EventPublisher;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Persistence\PDOAnnouncementRepository;
use DateMalformedStringException;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

readonly class EditAnnouncementUseCase
{
    public function __construct(
        private PDOAnnouncementRepository    $repository,
        private LoggerInterface              $logger,
        private AnnouncementValidationHelper $validator,
        private EventPublisher               $publisher,
    ) {}

    /**
     * Edits existing announcement
     * @param int $id
     * @param EditAnnouncementDTO $dto
     * @param int $adminId
     * @return int $id
     * @throws AnnouncementException
     * @throws Exception
     */
    public function execute(int $id, EditAnnouncementDTO $dto, int $adminId): int
    {
        $this->logger->info('Executing EditAnnouncementUseCase', [
            'announcement_id' => $id,
            'admin_id' => $adminId,
        ]);

        $this->validator->validateId($id);

        $announcement = $this->repository->findById($id);
        if (!$announcement) {
            throw AnnouncementException::notFound($id);
        }

        $this->validateBusinessRules($dto);

        $announcement->update(
            title: $dto->title,
            text: $dto->text,
            validUntil: $dto->validUntil,
            status: $dto->status,
            decidedBy: $adminId
        );

        $success = $this->repository->update($announcement);

        if (!$success) {
            throw AnnouncementException::failedToUpdate();
        }

        $this->publisher->publishAll($announcement->getDomainEvents());
        $announcement->clearDomainEvents();

        $this->logger->info('Announcement updated successfully', [
            'announcement_id' => $id,
            'admin_id' => $adminId,
        ]);

        return $id;
    }

    /**
     * Validates business logic
     * @throws AnnouncementException|DateMalformedStringException
     */
    private function validateBusinessRules(EditAnnouncementDTO $dto): void
    {
        $this->validator->validateTitle($dto->title);
        $this->validator->validateText($dto->text);
        $this->validator->validateValidUntilDate($dto->validUntil);
    }
}