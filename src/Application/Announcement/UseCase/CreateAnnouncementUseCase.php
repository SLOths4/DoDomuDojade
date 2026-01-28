<?php
declare(strict_types=1);

namespace App\Application\Announcement\UseCase;

use App\Application\Announcement\DTO\AddAnnouncementDTO;
use App\Domain\Announcement\Announcement;
use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementId;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Persistence\PDOAnnouncementRepository;
use DateMalformedStringException;
use Psr\Log\LoggerInterface;

/**
 * Use case for creating announcements
 */
readonly class CreateAnnouncementUseCase
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
    ){}

    /**
     * @param AddAnnouncementDTO $dto
     * @param int $adminId
     * @return AnnouncementId
     * @throws AnnouncementException
     * @throws DateMalformedStringException
     */
    public function execute(AddAnnouncementDTO $dto, int $adminId): AnnouncementId
    {
        $this->logger->info('Executing CreateAnnouncementUseCase', [
            'admin_id' => $adminId,
        ]);

        $this->validateBusinessRules($dto);

        $new = $this->mapDtoToEntity($dto, $adminId);

        $id = $this->repository->add($new);

        $events = $new->getDomainEvents();

        $new->clearDomainEvents();

        $this->logger->info('Announcement created successfully', [
            'announcement_id' => $id,
            'admin_id' => $adminId
        ]);

        return $id;
    }

    /**
     * @param AddAnnouncementDTO $dto
     * @param int $adminId
     * @return Announcement
     */
    private function mapDtoToEntity(
        AddAnnouncementDTO $dto,
        int $adminId
    ): Announcement {

       return Announcement::create(
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
