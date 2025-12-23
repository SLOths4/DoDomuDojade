<?php
declare(strict_types=1);

namespace App\Application\UseCase\Module;

use App\Domain\Entity\Module;
use App\Infrastructure\Repository\ModuleRepository;
use Exception;
use Psr\Log\LoggerInterface;

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
