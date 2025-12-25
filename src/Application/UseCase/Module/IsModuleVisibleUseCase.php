<?php
declare(strict_types=1);

namespace App\Application\UseCase\Module;

use App\Domain\Exception\ModuleException;
use App\Infrastructure\Repository\ModuleRepository;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use Exception;

readonly class IsModuleVisibleUseCase
{
    public function __construct(
        private ModuleRepository $repository,
        private LoggerInterface $logger,
        private string $DATE_FORMAT,
    ) {}

    /**
     * @throws Exception
     */
    public function execute(string $moduleName): bool
    {
        $this->logger->debug('Checking if module is visible', ['module_name' => $moduleName]);

        $now = new DateTimeImmutable();
        $formatted = $now->format($this->DATE_FORMAT);

        $module = $this->repository->findByName($moduleName);

        if (!$module) {
            throw ModuleException::notFound($module->id);
        }

        if (!$module->isActive) {
            $this->logger->debug('Module is not active', ['module_name' => $moduleName]);
            return false;
        }

        $visible = ($module->startTime <= $formatted && $module->endTime >= $formatted);

        $this->logger->debug('Module visibility evaluated', [
            'module_name' => $moduleName,
            'visible' => $visible,
        ]);

        return $visible;
    }
}
