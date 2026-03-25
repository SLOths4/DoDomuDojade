<?php
declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Domain\User\ForbiddenSelfDeleteException;
use App\Infrastructure\Persistence\PDOUserRepository;
use Psr\Log\LoggerInterface;

/**
 * Deletes user
 */
readonly class DeleteUserUseCase
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
     * @param int $activeUserId
     * @param int $targetUserId
     * @return bool
     */
    public function execute(int $activeUserId, int $targetUserId): bool
    {
        $this->logger->info('Executing DeleteUserUseCase', [
            'active_user_id' => $activeUserId,
            'target_user_id' => $targetUserId
        ]);

        if ($activeUserId === $targetUserId) {
            $this->logger->warning("User attempted to delete themselves", ['user_id' => $targetUserId]);
            throw new ForbiddenSelfDeleteException($targetUserId);
        }

        $this->repository->findById($targetUserId);

        $result = $this->repository->delete($targetUserId);

        $this->logger->info('User deleted finished', [
            'target_user_id' => $targetUserId,
            'success' => $result
        ]);

        return $result;
    }
}
