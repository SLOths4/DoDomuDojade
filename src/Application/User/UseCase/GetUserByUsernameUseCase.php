<?php
declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Domain\User\User;
use App\Infrastructure\Persistence\PDOUserRepository;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Fetches user by username
 */
readonly class GetUserByUsernameUseCase
{
    /**
     * @param PDOUserRepository $repository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private PDOUserRepository $repository,
        private LoggerInterface   $logger
    ) {}

    /**
     * @param string $username
     * @return User|null
     * @throws Exception
     */
    public function execute(string $username): ?User
    {
        $this->logger->debug('Executing GetUserByUsernameUseCase', ['username' => $username]);
        return $this->repository->findByExactUsername($username);
    }
}
