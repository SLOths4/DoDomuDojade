<?php
declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Infrastructure\Persistence\PDOUserRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class DeleteUserUseCase
{
    public function __construct(
        private PDOUserRepository $repository,
        private LoggerInterface   $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(int $activeUserId, int $targetUserId): bool
    {
        $this->logger->info('Executing DeleteUserUseCase', [
            'active_user_id' => $activeUserId,
            'target_user_id' => $targetUserId
        ]);

        if ($activeUserId === $targetUserId) {
            $this->logger->warning("User attempted to delete themselves", ['user_id' => $targetUserId]);
            throw new Exception("User can't delete themselves.");
        }

        // Verify user exists
        $this->repository->findById($targetUserId);

        $result = $this->repository->delete($targetUserId);

        $this->logger->info('User deleted finished', [
            'target_user_id' => $targetUserId,
            'success' => $result
        ]);

        return $result;
    }
}
