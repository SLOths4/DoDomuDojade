<?php
declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\User;
use App\Infrastructure\Persistence\UserRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class UpdateUserUseCase
{
    public function __construct(
        private UserRepository $repository,
        private LoggerInterface $logger,
        private int $maxUsernameLength,
        private int $minPasswordLength
    ) {}

    /**
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
