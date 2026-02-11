<?php
declare(strict_types=1);

namespace App\Application\Module\UseCase;

use App\Domain\Module\ModuleException;
use App\Infrastructure\Helper\ModuleValidationHelper;
use App\Infrastructure\Persistence\PDOModuleRepository;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Toggles module
 */
readonly class ToggleModuleUseCase
{
    public function __construct(
        private PDOModuleRepository    $repository,
        private LoggerInterface        $logger,
        private ModuleValidationHelper $validator,
    ) {}

    /**
     * @param int $id
     * @return bool
     * @throws ModuleException
     */
    public function execute(int $id): bool
    {
        $this->logger->info('Toggling module active status', ['module_id' => $id]);

        $this->validator->validateId($id);

        $module = $this->repository->findById($id);
        if (!$module) {
            throw ModuleException::notFound($id);
        }

        $module->toggle();

        $result = $this->repository->update($module);

        if (!$result) {
            throw ModuleException::failedToToggle();
        }

        $this->logger->info('Module toggle finished', [
            'module_id' => $id,
            'new_status' => $module->isActive,
        ]);

        return true;
    }
}
