<?php
declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Infrastructure\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Exception;

readonly class ChangePasswordUseCase
{
    public function __construct(
        private UserRepository $repository,
        private LoggerInterface $logger,
        private int $minPasswordLength
    ) {}

    /**
     * @throws Exception
     */
    public function execute(int $id, string $newPassword): bool
    {
        $this->logger->info('Changing user password', ['user_id' => $id]);

        if (strlen($newPassword) < $this->minPasswordLength) {
            $this->logger->warning('New password is too short', [
                'user_id' => $id,
                'provided_length' => strlen($newPassword),
                'min_length' => $this->minPasswordLength,
            ]);
            throw new Exception("Password too short");
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $result = $this->repository->updatePassword($id, $newPasswordHash);

        $this->logger->info('Password change finished', [
            'user_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }
}
