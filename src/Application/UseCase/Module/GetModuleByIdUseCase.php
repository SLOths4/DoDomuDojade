<?php
declare(strict_types=1);

namespace App\Application\UseCase\Module;

use App\Domain\Module;
use App\Infrastructure\Repository\ModuleRepository;
use Psr\Log\LoggerInterface;
use Exception;

readonly class GetModuleByIdUseCase
{
    public function __construct(
        private ModuleRepository $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(int $id): ?Module
    {
        $this->logger->debug('Fetching module by id', ['module_id' => $id]);
        $module = $this->repository->findById($id);
        $this->logger->debug('Fetched module by id', ['module_id' => $id, 'found' => $module !== null]);
        return $module;
    }
}
