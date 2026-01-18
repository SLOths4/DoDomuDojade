<?php
declare(strict_types=1);

namespace App\Application\Module\UseCase;

use App\Infrastructure\Persistence\PDOModuleRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class GetAllModulesUseCase
{
    public function __construct(
        private PDOModuleRepository $repository,
        private LoggerInterface     $logger
    ) {}

    /**
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
