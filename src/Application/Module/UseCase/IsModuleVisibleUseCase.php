<?php
declare(strict_types=1);

namespace App\Application\Module\UseCase;

use App\Domain\Module\ModuleException;
use App\Domain\Module\ModuleName;
use App\Domain\Module\ModuleRepositoryInterface;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Checks if a module is visible
 */
readonly class IsModuleVisibleUseCase
{
    /**
     * @param ModuleRepositoryInterface $repository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ModuleRepositoryInterface $repository,
        private LoggerInterface     $logger,
    ) {}

    /**
     * @param ModuleName $moduleName
     * @return bool
     * @throws ModuleException
     */
    public function execute(ModuleName $moduleName): bool
    {
        $this->logger->debug('Checking if module is visible', ['module_name' => $moduleName]);

        $now = new DateTimeImmutable();

        $module = $this->repository->findByName($moduleName);

        if (!$module) {
            throw ModuleException::notFound();
        }

        if (!$module->isActive) {
            $this->logger->info('Module is not active', ['module_name' => $moduleName]);
            return false;
        }

        $visible = ($module->startTime <= $now && $now <= $module->endTime);

        if (!$visible) {
            $this->logger->info('Module outside time window', [
                'module_name' => $moduleName,
                'start_time'  => $module->startTime->format('H:i:s'),
                'end_time'    => $module->endTime->format('H:i:s'),
                'now'         => $now->format('H:i:s'),
            ]);
        }

        return $visible;
    }
}
