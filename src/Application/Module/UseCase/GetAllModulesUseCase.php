<?php
declare(strict_types=1);

namespace App\Application\Module\UseCase;

use App\Domain\Module\ModuleRepositoryInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Fetches all modules
 */
readonly class GetAllModulesUseCase
{
    /**
     * @param ModuleRepositoryInterface $repository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ModuleRepositoryInterface $repository,
        private LoggerInterface     $logger
    ) {}

    /**
     * @return array<\App\Domain\Module\Module>
     * @throws Exception
     */
    public function execute(): array
    {
        $this->logger->debug('Fetching all modules');
        $modules = $this->repository->findAll();
        $this->logger->debug('Fetched all modules');
        return $modules;
    }
}
