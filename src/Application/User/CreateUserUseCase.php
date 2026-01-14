<?php
declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\Password;
use App\Domain\User\User;
use App\Domain\User\Username;
use App\Infrastructure\Persistence\UserRepository;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

readonly class CreateUserUseCase
{
    public function __construct(
        private UserRepository $repository,
        private LoggerInterface $logger,
        private int $maxUsernameLength = 255,
        private int $minPasswordLength = 8
    ) {}

    /**
     * @throws Exception
     */
    public function execute(string $rawUsername, string $rawPassword): bool
    {
        $this->logger->info('Executing CreateUserUseCase', ['username' => $rawUsername]);

        $username = new Username($rawUsername, $this->maxUsernameLength);
        $password = new Password($rawPassword, $this->minPasswordLength);

        if ($this->repository->findByExactUsername($username->value)) {
            $this->logger->warning('User already exists', ['username' => $rawUsername]);
            throw new Exception("User already exists");
        }

        $user = new User(
            null,
            $username->value,
            $password->getHash(),
            new DateTimeImmutable()
        );

        $result = $this->repository->add($user);

        $this->logger->info('User created successfully', ['username' => $rawUsername, 'success' => $result]);

        return $result;
    }
}
