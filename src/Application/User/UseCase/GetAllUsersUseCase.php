<?php
declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Domain\User\User;
use App\Infrastructure\Persistence\PDOUserRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class GetAllUsersUseCase
{
    public function __construct(
        private PDOUserRepository $repository,
        private LoggerInterface   $logger
    ) {}

    /**
     * @return User[]
     * @throws Exception
     */
    public function execute(): array
    {
        $this->logger->debug('Executing GetAllUsersUseCase');
        return $this->repository->findAll();
    }
}
