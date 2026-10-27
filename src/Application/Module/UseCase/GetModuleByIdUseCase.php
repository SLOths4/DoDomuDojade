<?php
declare(strict_types=1);

namespace App\Application\Module\UseCase;

use App\Domain\Module\Module;
use App\Domain\Module\ModuleException;
use App\Domain\Module\ModuleBusinessValidator;
use App\Domain\Module\ModuleRepositoryInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Fetches module by id
 */
readonly class GetModuleByIdUseCase
{
    /**
     * @param ModuleRepositoryInterface $repository
     * @param LoggerInterface $logger
     * @param ModuleBusinessValidator $validator
     */
    public function __construct(
        private ModuleRepositoryInterface    $repository,
        private LoggerInterface        $logger,
        private ModuleBusinessValidator $validator,
    ) {}

    /**
     * @param int $id
     * @return Module|null
     * @throws ModuleException
     */
    public function execute(int $id): ?Module
    {
        $this->logger->debug('Fetching module by id', ['module_id' => $id]);
        $this->validator->validateId($id);
        $module = $this->repository->findById($id);
        if (!$module) {
            throw ModuleException::notFound($id);
        }
        $this->logger->debug('Fetched module by id', ['module_id' => $id]);
        return $module;
    }
}
