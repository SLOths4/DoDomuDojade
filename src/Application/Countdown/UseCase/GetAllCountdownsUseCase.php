<?php
declare(strict_types=1);

namespace App\Application\Countdown\UseCase;

use App\Infrastructure\Persistence\PDOCountdownRepository;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Fetches all available countdowns
 */
readonly class GetAllCountdownsUseCase
{
    public function __construct(
        private PDOCountdownRepository $repository,
        private LoggerInterface        $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(): array
    {
        $this->logger->debug('Fetching all countdowns');
        return $this->repository->findAll();
    }
}
