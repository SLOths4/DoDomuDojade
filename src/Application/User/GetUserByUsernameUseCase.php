<?php
declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\User;
use App\Infrastructure\Persistence\UserRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class GetUserByUsernameUseCase
{
    public function __construct(
        private UserRepository $repository,
        private LoggerInterface $logger
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
