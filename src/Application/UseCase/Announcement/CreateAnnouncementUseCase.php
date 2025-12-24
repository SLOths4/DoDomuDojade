<?php
declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\Application\DataTransferObject\AddAnnouncementDTO;
use App\Domain\Entity\Announcement;
use App\Domain\Exception\AnnouncementException;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Repository\AnnouncementRepository;
use DateMalformedStringException;
use Exception;
use Psr\Log\LoggerInterface;

readonly class CreateAnnouncementUseCase
{
    public function __construct(
        private AnnouncementRepository $repository,
        private LoggerInterface $logger,
        private AnnouncementValidationHelper $validator,
    ){}

    /**
     * @throws Exception
     */
    public function execute(AddAnnouncementDTO $dto, int $adminId): int
    {
        $this->logger->info('Executing CreateAnnouncementUseCase', [
            'admin_id' => $adminId,
        ]);

        $this->validateBusinessRules($dto);

        $new = $this->mapDtoToEntity($dto, $adminId);

        $id = $this->repository->add($new);

        $this->logger->info('Announcement created successfully', [
            'announcement_id' => $id,
            'admin_id' => $adminId
        ]);

        return $id;
    }

    /**
     *
     * @param AddAnnouncementDTO $dto
     * @param int $adminId
     * @return Announcement
     */
    private function mapDtoToEntity(
        AddAnnouncementDTO $dto,
        int $adminId
    ): Announcement {

       return Announcement::createNew(
            title: $dto->title,
            text: $dto->text,
            validUntil: $dto->validUntil,
            userId: $adminId,
       );
    }

    /**
     * Validates business logic
     * @throws AnnouncementException|DateMalformedStringException
     */
    private function validateBusinessRules(AddAnnouncementDTO $dto): void
    {
        $this->validator->validateTitle($dto->title);
        $this->validator->validateText($dto->text);
        $this->validator->validateValidUntilDate($dto->validUntil);
    }
}
