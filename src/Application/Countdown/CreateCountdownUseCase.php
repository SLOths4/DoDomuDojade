<?php
declare(strict_types=1);

namespace App\Application\Countdown;

use App\Domain\Countdown\Countdown;
use App\Domain\Exception\CountdownException;
use App\Infrastructure\Helper\CountdownValidationHelper;
use App\Infrastructure\Persistence\CountdownRepository;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Creates countdown with provided data
 */
readonly class CreateCountdownUseCase
{
    public function __construct(
        private CountdownRepository $repository,
        private LoggerInterface $logger,
        private CountdownValidationHelper $validator,
    ) {}

    /**
     * @throws Exception
     */
    public function execute(AddEditCountdownDTO $dto, int $adminId): int
    {
        $this->logger->info('Creating countdown', [
            'admin_id' => $adminId,
        ]);

        $this->validateBusinessRules($dto);

        $new = $this->mapDtoToEntity($dto, $adminId);

        $id = $this->repository->add($new);

        if (!$id) {
            throw CountdownException::failedToCreate();
        }

        $this->logger->info('Countdown creation finished', [
            'admin_id' => $adminId,
            'countdown_id' => $id,
        ]);

        return $id;
    }

    /**
     *
     * @param AddEditCountdownDTO $dto
     * @param int $adminId
     * @return Countdown
     */
    private function mapDtoToEntity(
        AddEditCountdownDTO $dto,
        int $adminId
    ): Countdown {

        return new Countdown(
            id: null,
            title: $dto->title,
            countTo: $dto->countTo,
            userId: $adminId,
        );
    }

    /**
     * @throws Exception
     */
    private function validateBusinessRules(AddEditCountdownDTO $dto): void
    {
        $this->validator->validateTitle($dto->title);
        $this->validator->validateCountToDate($dto->countTo);
    }
}
