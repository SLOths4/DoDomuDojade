<?php
declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Domain\User;
use App\Infrastructure\Repository\UserRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class GetAllUsersUseCase
{
    public function __construct(
        private UserRepository $repository,
        private LoggerInterface $logger
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
