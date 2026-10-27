<?php
declare(strict_types=1);

namespace App\Application\Countdown\UseCase;

use App\Application\Countdown\AddEditCountdownDTO;
use App\Domain\Countdown\CountdownException;
use App\Domain\Event\EventPublisher;
use App\Domain\Countdown\CountdownBusinessValidator;
use App\Domain\Countdown\CountdownRepositoryInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Updates countdown
 */
readonly class UpdateCountdownUseCase
{
    /**
     * @param CountdownRepositoryInterface $repository
     * @param LoggerInterface $logger
     * @param CountdownBusinessValidator $validator
     */
    public function __construct(
        private EventPublisher            $eventPublisher,
        private CountdownRepositoryInterface    $repository,
        private LoggerInterface           $logger,
        private CountdownBusinessValidator $validator,
    ) {}

    /**
     * @param int $id
     * @param AddEditCountdownDTO $dto
     * @param int $adminId
     * @return bool
     * @throws CountdownException
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

        $existing->updateDetails($dto->title, $dto->countTo, $adminId);

        $result = $this->repository->update($existing);

        if (!$result){
            throw CountdownException::failedToUpdate();
        }

        $events = $existing->getDomainEvents();
        $this->eventPublisher->publishAll($events);
        $existing->clearDomainEvents();

        $this->logger->info('Countdown update finished', [
            'countdown_id' => $id,
            'admin_id' => $adminId,
        ]);

        return true;
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
