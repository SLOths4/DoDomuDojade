<?php
declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\Application\DataTransferObject\EditAnnouncementDTO;
use App\Domain\Entity\Announcement;
use App\Domain\Exception\AnnouncementException;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Repository\AnnouncementRepository;
use DateMalformedStringException;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

readonly class EditAnnouncementUseCase
{
    public function __construct(
        private AnnouncementRepository $repository,
        private LoggerInterface $logger,
        private AnnouncementValidationHelper $validator,
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

        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw AnnouncementException::notFound($id);
        }

        $this->validateBusinessRules($dto);

        $updated = $this->mapDtoToEntity($dto, $existing, $adminId);

        $success = $this->repository->update($updated);

        if (!$success) {
            throw AnnouncementException::failedToUpdate();
        }

        $this->logger->info('Announcement updated successfully', [
            'announcement_id' => $id,
            'admin_id' => $adminId,
        ]);

        return $id;
    }

    /**
     * Maps DTO to entity
     * @param EditAnnouncementDTO $dto
     * @param Announcement $existing
     * @param int $adminId
     * @return Announcement
     */
    private function mapDtoToEntity(
        EditAnnouncementDTO $dto,
        Announcement $existing,
        int $adminId,
    ): Announcement {
        $statusChanged = $dto->status !== null && $dto->status !== $existing->status;

        return new Announcement(
            id: $existing->id,
            title: $dto->title,
            text: $dto->text,
            createdAt: $existing->createdAt,
            validUntil: $dto->validUntil,
            userId: $existing->userId,
            status: $dto->status ?? $existing->status,
            decidedAt: $statusChanged ? new DateTimeImmutable() : $existing->decidedAt,
            decidedBy: $statusChanged ? $adminId : $existing->decidedBy,
        );
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