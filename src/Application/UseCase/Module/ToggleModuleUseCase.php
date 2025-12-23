<?php
declare(strict_types=1);

namespace App\Application\UseCase\Module;

use App\Domain\Entity\Module;
use App\Infrastructure\Repository\ModuleRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class ToggleModuleUseCase
{
    public function __construct(
        private ModuleRepository $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(int $id): bool
    {
        $this->logger->info('Toggling module active status', ['module_id' => $id]);

        $module = $this->repository->findById($id);
        if ($module === null) {
            $this->logger->warning('Module does not exist', ['module_id' => $id]);
            throw new Exception("Module with id: $id does not exist");
        }

        $toggledModule = new Module(
            $module->id,
            $module->moduleName,
            !$module->isActive,
            $module->startTime,
            $module->endTime
        );

        $result = $this->repository->update($toggledModule);

        $this->logger->info('Module toggle finished', [
            'module_id' => $id,
            'success' => $result,
            'new_status' => !$module->isActive,
        ]);

        return $result;
    }
}
