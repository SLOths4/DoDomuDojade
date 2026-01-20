<?php
declare(strict_types=1);

namespace App\Application\Countdown\UseCase;

use App\Application\Countdown\AddEditCountdownDTO;
use App\Domain\Countdown\Countdown;
use App\Domain\Countdown\CountdownException;
use App\Domain\Countdown\Event\CountdownUpdatedEvent;
use App\Domain\Event\EventPublisher;
use App\Infrastructure\Helper\CountdownValidationHelper;
use App\Infrastructure\Persistence\PDOCountdownRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class UpdateCountdownUseCase
{
    public function __construct(
        private PDOCountdownRepository    $repository,
        private LoggerInterface           $logger,
        private CountdownValidationHelper $validator,
    ) {}

    /**
     * @throws Exception
     */
    public function execute(int $id, AddEditCountdownDTO $dto, int $adminId): bool
    {
        $this->logger->info('Updating countdown', [
            'countdown_id' => $id,
            'admin_id' => $adminId,
        ]);

        $this->validator->validateId($id);

        $this->validateBusinessRules($dto);

        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw CountdownException::notFound($id);
        }

        $updated = $this->mapDtoToEntity($dto, $existing, $adminId);

        $result = $this->repository->update($updated);

        if (!$result){
            throw CountdownException::failedToUpdate();
        }

        $this->logger->info('Countdown update finished', [
            'countdown_id' => $id,
            'admin_id' => $adminId,
        ]);

        return true;
    }

    /**
     * Maps DTO to entity
     * @param AddEditCountdownDTO $dto
     * @param Countdown $existing
     * @param int $adminId
     * @return Countdown
     */
    private function mapDtoToEntity(
        AddEditCountdownDTO $dto,
        Countdown $existing,
        int $adminId,
    ): Countdown {
        return new Countdown(
          id: $existing->id,
          title: $dto->title,
          countTo: $dto->countTo,
          userId: $adminId,
        );
    }

    /**
     * Validates business logic
     * @throws Exception
     */
    private function validateBusinessRules(AddEditCountdownDTO $dto): void
    {
        $this->validator->validateTitle($dto->title);
        $this->validator->validateCountToDate($dto->countTo);
    }
}
