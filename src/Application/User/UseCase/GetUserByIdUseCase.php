<?php
declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Domain\User\User;
use App\Infrastructure\Persistence\PDOUserRepository;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Fetches user by id
 */
readonly class GetUserByIdUseCase
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
     * @param int $id
     * @return User
     * @throws Exception
     */
    public function execute(int $id): User
    {
        $this->logger->debug('Executing GetUserByIdUseCase', ['user_id' => $id]);
        return $this->repository->findById($id);
    }
}
