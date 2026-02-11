<?php
namespace App\Application\User\UseCase;

use App\Application\User\AuthenticateUserDTO;
use App\Domain\Shared\AuthenticationException;
use App\Domain\User\User;
use App\Infrastructure\Persistence\PDOUserRepository;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Authenticates user
 */
final readonly class AuthenticateUserUseCase
{
    /**
     * @param PDOUserRepository $userRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private PDOUserRepository $userRepository,
        private LoggerInterface   $logger
    ){}

    /**
     * @throws AuthenticationException
     * @throws Exception
     */
    public function execute(AuthenticateUserDTO $dto): User
    {
        $this->logger->debug("User authentication attempt");

        try {
            $dto->validate();
        } catch (AuthenticationException $e) {
            $this->logger->debug("Empty credentials");
            throw $e;
        }

        $user = $this->userRepository->findByExactUsername($dto->username);
        $this->logger->debug("Finding user");

        if (!$user || !password_verify($dto->password, $user->passwordHash)) {
            $this->logger->debug("Invalid credentials");
            throw AuthenticationException::invalidCredentials();
        }

        $this->logger->debug("User authenticated successfully");
        return $user;
    }
}
