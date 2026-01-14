<?php
declare(strict_types=1);

namespace App\Application\Module;

use App\Domain\Module\Module;
use App\Domain\Module\ModuleException;
use App\Infrastructure\Helper\ModuleValidationHelper;
use App\Infrastructure\Persistence\ModuleRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class UpdateModuleUseCase
{
    public function __construct(
        private ModuleRepository $repository,
        private LoggerInterface $logger,
        private ModuleValidationHelper $validator,
    ) {}

    /**
     * @throws Exception
     */
    public function execute(int $id, EditModuleDTO $dto): bool
    {
        $this->logger->info('Updating module', [
            'module_id' => $id,
        ]);

        $this->validator->validateId($id);

        $module = $this->repository->findById($id);
        if (!$module) {
            throw ModuleException::notFound($id);
        }

        $this->validateBusinessRules($dto);

        $updated = $this->mapDtoToEntity($dto, $module);

        $result = $this->repository->update($updated);

        if (!$result) {
            throw ModuleException::failedToUpdate();
        }

        $this->logger->info('Module update finished', [
            'module_id' => $id,
        ]);

        return true;
    }

    /**
     * Maps DTO to entity
     * @param EditModuleDTO $dto
     * @param Module $existing
     * @return Module
     */
    private function mapDtoToEntity(
        EditModuleDTO $dto,
        Module $existing,
    ): Module {
        return new Module(
            id: $existing->id,
            moduleName: $existing->moduleName,
            isActive: $existing->isActive,
            startTime: $dto->startTime,
            endTime: $dto->endTime,
        );
    }

    /**
     * Validates business logic
     * @throws Exception
     */
    private function validateBusinessRules(EditModuleDTO $dto): void
    {
        $this->validator->validateStartTime($dto->startTime);
        $this->validator->validateEndTime($dto->startTime);
        $this->validator->validateStartTimeNotGreaterThanEndTime($dto->startTime, $dto->endTime);
    }
}
