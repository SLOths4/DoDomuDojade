<?php
declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\User;
use App\Infrastructure\Persistence\UserRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class GetUserByIdUseCase
{
    public function __construct(
        private UserRepository $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(int $id): User
    {
        $this->logger->debug('Executing GetUserByIdUseCase', ['user_id' => $id]);
        return $this->repository->findById($id);
    }
}
