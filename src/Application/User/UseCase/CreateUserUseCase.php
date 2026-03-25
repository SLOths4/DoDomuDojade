<?php
declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Application\User\CreateUserDTO;
use App\Domain\User\User;
use App\Domain\User\UserAlreadyExistsException;
use App\Domain\User\ValueObject\Password;
use App\Domain\User\ValueObject\Username;
use App\Domain\User\UserRepositoryInterface;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

/**
 * Creates new user
 */
readonly class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private LoggerInterface   $logger,
        private int               $maxUsernameLength = 255,
        private int               $minPasswordLength = 8
    ) {}

        public function execute(CreateUserDTO $dto): int
    {
        $this->logger->info('Executing CreateUserUseCase');

        $username = new Username($dto->username, $this->maxUsernameLength);
        $password = new Password($dto->password, $this->minPasswordLength);

        if ($this->repository->findByExactUsername($username->value)) {
            $this->logger->warning('User already exists');
            throw new UserAlreadyExistsException($username->value);
        }

        $user = new User(
            null,
            $username->value,
            $password->getHash(),
            new DateTimeImmutable()
        );

        $result = $this->repository->add($user);

        $this->logger->info('User created successfully');

        return $result;
    }
}
