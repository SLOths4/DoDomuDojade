<?php
declare(strict_types=1);

namespace App\Application\Announcement\UseCase;

use App\Application\Announcement\DTO\EditAnnouncementDTO;
use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementId;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Persistence\PDOAnnouncementRepository;
use DateMalformedStringException;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Use case for editing announcements
 */
readonly class EditAnnouncementUseCase
{
    /**
     * @param PDOAnnouncementRepository $repository
     * @param LoggerInterface $logger
     * @param AnnouncementValidationHelper $validator
     */
    public function __construct(
        private PDOAnnouncementRepository    $repository,
        private LoggerInterface              $logger,
        private AnnouncementValidationHelper $validator,
    ) {}

    /**
     * Edits existing announcement
     * @param AnnouncementId $id
     * @param EditAnnouncementDTO $dto
     * @param int $adminId
     * @return AnnouncementId $id
     * @throws AnnouncementException
     * @throws Exception
     */
    public function execute(AnnouncementId $id, EditAnnouncementDTO $dto, int $adminId): AnnouncementId
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