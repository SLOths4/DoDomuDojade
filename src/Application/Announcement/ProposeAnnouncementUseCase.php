<?php
declare(strict_types=1);

namespace App\Application\Announcement;

use App\Domain\Announcement\Announcement;
use App\Domain\Announcement\AnnouncementException;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Persistence\PDOAnnouncementRepository;
use DateMalformedStringException;
use Exception;
use Psr\Log\LoggerInterface;

readonly class ProposeAnnouncementUseCase
{
    public function __construct(
        private PDOAnnouncementRepository    $repository,
        private AnnouncementValidationHelper $validator,
        private LoggerInterface              $logger
    ) {}

    /**
     * Adds a new announcement
     * @param ProposeAnnouncementDTO $dto
     * @return int
     * @throws AnnouncementException
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function execute(ProposeAnnouncementDTO $dto): int
    {
        $this->logger->info('Proposing announcement', [
            'title' => $dto->title,
            'valid_until' => $dto->validUntil->format('Y-m-d')
        ]);

        $this->validateBusinessRules($dto);

        $announcement = $this->mapDtoToEntity($dto);

        $id = $this->repository->add($announcement);

        if (!$id) {
            throw AnnouncementException::failedToCreate();
        }

        $this->logger->info('Announcement proposed successfully', ['id' => $id]);

        return $id;
    }

    /**
     * Maps DTO to entity
     * @param ProposeAnnouncementDTO $dto
     * @return Announcement
     */
    private function mapDtoToEntity(ProposeAnnouncementDTO $dto): Announcement
    {
        return Announcement::proposeNew(
            title: $dto->title,
            text: $dto->text,
            validUntil: $dto->validUntil
        );
    }

    /**
     * Validates business logic
     * @throws DateMalformedStringException
     * @throws AnnouncementException
     */
    private function validateBusinessRules(ProposeAnnouncementDTO $dto): void
    {
        $this->validator->validateTitle($dto->title);
        $this->validator->validateText($dto->text);
        $this->validator->validateValidUntilDate($dto->validUntil);
    }
}
