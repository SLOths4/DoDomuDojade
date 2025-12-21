<?php
declare(strict_types=1);

namespace App\Application\UseCase\Module;

use App\Infrastructure\Repository\ModuleRepository;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use Exception;

readonly class IsModuleVisibleUseCase
{
    public function __construct(
        private ModuleRepository $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(string $moduleName): bool
    {
        $this->logger->debug('Checking if module is visible', ['module_name' => $moduleName]);

        $now = new DateTimeImmutable();
        $module = $this->repository->findByName($moduleName);

        if ($module === null) {
            $this->logger->warning('Module not found when checking visibility', ['module_name' => $moduleName]);
            throw new Exception("Module not found with name: $moduleName");
        }

        if (!$module->isActive) {
            $this->logger->debug('Module is not active', ['module_name' => $moduleName]);
            return false;
        }

        $visible = ($module->startTime <= $now && $module->endTime >= $now);

        $this->logger->debug('Module visibility evaluated', [
            'module_name' => $moduleName,
            'visible' => $visible,
        ]);

        return $visible;
    }
}
