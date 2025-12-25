<?php
declare(strict_types=1);

namespace App\Application\UseCase\Module;

use App\Domain\Entity\Module;
use App\Domain\Exception\ModuleException;
use App\Infrastructure\Repository\ModuleRepository;
use App\Infrastructure\Helper\ModuleValidationHelper;
use Exception;
use Psr\Log\LoggerInterface;

readonly class GetModuleByIdUseCase
{
    public function __construct(
        private ModuleRepository $repository,
        private LoggerInterface $logger,
        private ModuleValidationHelper $validator,
    ) {}

    /**
     * @throws Exception
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
