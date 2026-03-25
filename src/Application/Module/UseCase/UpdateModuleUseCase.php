<?php
declare(strict_types=1);

namespace App\Application\Module\UseCase;

use App\Application\Module\EditModuleDTO;
use App\Domain\Event\EventPublisher;
use App\Domain\Module\ModuleException;
use App\Infrastructure\Helper\ModuleValidationHelper;
use App\Infrastructure\Persistence\PDOModuleRepository;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Updates module
 */
readonly class UpdateModuleUseCase
{
    public function __construct(
        private PDOModuleRepository    $repository,
        private EventPublisher         $eventPublisher,
        private LoggerInterface        $logger,
        private ModuleValidationHelper $validator,
    ) {}

    /**
     * @param int $id
     * @param EditModuleDTO $dto
     * @return bool
     * @throws ModuleException
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

        $module->updateSchedule($dto->startTime, $dto->endTime);

        $result = $this->repository->update($module);

        if (!$result) {
            throw ModuleException::failedToUpdate();
        }

        $events = $module->getDomainEvents();
        $this->eventPublisher->publishAll($events);
        $module->clearDomainEvents();

        $this->logger->info('Module update finished', [
            'module_id' => $id,
        ]);

        return true;
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
