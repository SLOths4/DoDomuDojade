<?php
namespace App\Application\User\UseCase;

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
    public function execute(string $username, string $password): User
    {
        $this->logger->debug("User authentication attempt");

        if (empty($username) || empty($password)) {
            throw AuthenticationException::emptyCredentials();
        }

        $user = $this->userRepository->findByExactUsername($username);

        if (!$user || !password_verify($password, $user->passwordHash)) {
            throw AuthenticationException::invalidCredentials();
        }

        $this->logger->debug("User authenticated successfully");
        return $user;
    }
}
