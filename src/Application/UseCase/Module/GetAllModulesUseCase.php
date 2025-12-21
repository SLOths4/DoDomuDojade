<?php
declare(strict_types=1);

namespace App\Application\UseCase\Module;

use App\Infrastructure\Repository\ModuleRepository;
use Psr\Log\LoggerInterface;
use Exception;

readonly class GetAllModulesUseCase
{
    public function __construct(
        private ModuleRepository $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(): array
    {
        $this->logger->debug('Fetching all modules');
        $modules = $this->repository->findAll();
        $this->logger->debug('Fetched all modules', ['count' => count($modules)]);
        return $modules;
    }
}
