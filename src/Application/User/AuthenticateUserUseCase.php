<?php
namespace App\Application\User;

use App\Domain\Exception\AuthenticationException;
use App\Domain\User\User;
use App\Infrastructure\Persistence\UserRepository;
use Exception;
use Psr\Log\LoggerInterface;

final readonly class AuthenticateUserUseCase
{
    public function __construct(
        private UserRepository  $userRepository,
        private LoggerInterface $logger
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

        $user = $this->userRepository->findByUsername($username);

        if (!$user || !password_verify($password, $user->passwordHash)) {
            throw AuthenticationException::invalidCredentials();
        }

        $this->logger->debug("User authenticated successfully");
        return $user;
    }
}
