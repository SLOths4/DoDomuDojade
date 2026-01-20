<?php
declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Domain\User\User;
use App\Infrastructure\Persistence\PDOUserRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class GetUserByUsernameUseCase
{
    public function __construct(
        private PDOUserRepository $repository,
        private LoggerInterface   $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(string $username): ?User
    {
        $this->logger->debug('Executing GetUserByUsernameUseCase', ['username' => $username]);
        return $this->repository->findByExactUsername($username);
    }
}
