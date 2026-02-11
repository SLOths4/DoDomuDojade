<?php
declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Domain\User\ValueObject\Password;
use App\Infrastructure\Persistence\PDOUserRepository;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Changes password for a user
 */
readonly class ChangePasswordUseCase
{
    /**
     * @param PDOUserRepository $repository
     * @param LoggerInterface $logger
     * @param int $minPasswordLength
     */
    public function __construct(
        private PDOUserRepository $repository,
        private LoggerInterface   $logger,
        private int               $minPasswordLength
    ) {}

    /**
     * @param int $id
     * @param string $newPassword
     * @return bool
     * @throws Exception
     */
    public function execute(int $id, string $newPassword): bool
    {
        $this->logger->info('Changing user password', ['user_id' => $id]);

        $password = new Password($newPassword, $this->minPasswordLength);
        $result = $this->repository->updatePassword($id, $password->getHash());

        $this->logger->info('Password change finished', [
            'user_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }
}
