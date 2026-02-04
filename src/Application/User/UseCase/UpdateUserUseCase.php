<?php
declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Application\User\EditUserDTO;
use App\Domain\User\User;
use App\Domain\User\ValueObject\Password;
use App\Domain\User\ValueObject\Username;
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
     * @param EditUserDTO $dto
     * @return bool
     * @throws Exception
     */
    public function execute(int $id, EditUserDTO $dto): bool
    {
        $this->logger->info('Updating user', [
            'user_id' => $id,
            'payload_keys' => array_filter([
                'username',
                $dto->password !== null ? 'password' : null,
            ]),
        ]);

        $user = $this->repository->findById($id);

        $username = new Username($dto->username, $this->maxUsernameLength);
        $passwordHash = $user->passwordHash;

        if ($dto->password !== null) {
            $password = new Password($dto->password, $this->minPasswordLength);
            $passwordHash = $password->getHash();
        }

        $updatedUser = new User(
            $user->id,
            $username->value,
            $passwordHash,
            $user->createdAt
        );

        $result = $this->repository->update($updatedUser);

        $this->logger->info('User update finished', [
            'user_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }
}
