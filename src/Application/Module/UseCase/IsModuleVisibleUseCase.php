<?php
declare(strict_types=1);

namespace App\Application\Module\UseCase;

use App\Domain\Module\ModuleException;
use App\Domain\Module\ModuleName;
use App\Infrastructure\Persistence\PDOModuleRepository;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Checks if a module is visible
 */
readonly class IsModuleVisibleUseCase
{
    /**
     * @param PDOModuleRepository $repository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private PDOModuleRepository $repository,
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
            throw ModuleException::notFound($module->id);
        }

        if (!$module->isActive) {
            $this->logger->debug('Module is not active', ['module_name' => $moduleName]);
            return false;
        }

        $visible = ($module->startTime <= $now && $now <= $module->endTime);

        $this->logger->debug('Module visibility evaluated', [
            'module_name' => $moduleName,
            'visible' => $visible,
        ]);

        return $visible;
    }
}
