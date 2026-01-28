<?php
declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Domain\User\User;
use App\Infrastructure\Persistence\PDOUserRepository;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Updates user
 */
readonly class UpdateUserUseCase
{
    /**
     * @param PDOUserRepository $repository
     * @param LoggerInterface $logger
     * @param int $maxUsernameLength
     * @param int $minPasswordLength
     */
    public function __construct(
        private PDOUserRepository $repository,
        private LoggerInterface   $logger,
        private int               $maxUsernameLength,
        private int               $minPasswordLength
    ) {}

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function execute(int $id, array $data): bool
    {
        $this->logger->info('Updating user', [
            'user_id' => $id,
            'payload_keys' => array_keys($data),
        ]);

        $this->validate($data);
        $user = $this->repository->findById($id);

        $updatedUser = new User(
            $user->id,
            isset($data['username']) ? trim($data['username']) : $user->username,
            $data['password_hash'] ?? $user->passwordHash,
            $user->createdAt
        );

        $result = $this->repository->update($updatedUser);

        $this->logger->info('User update finished', [
            'user_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * @param array $data
     * @return void
     * @throws Exception
     */
    private function validate(array $data): void
    {
        if (isset($data['username']) && strlen($data['username']) > $this->maxUsernameLength) {
            throw new Exception('Username too long');
        }
        if (isset($data['password_hash']) && strlen($data['password_hash']) < $this->minPasswordLength) {
            throw new Exception('Password hash too short');
        }
    }
}
