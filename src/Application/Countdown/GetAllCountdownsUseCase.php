<?php
declare(strict_types=1);

namespace App\Application\Countdown;

use App\Domain\Exception\CountdownException;
use App\Infrastructure\Persistence\CountdownRepository;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Fetches all available countdowns
 */
readonly class GetAllCountdownsUseCase
{
    public function __construct(
        private CountdownRepository $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(): array
    {
        $this->logger->debug('Fetching all countdowns');
        $countdowns = $this->repository->findAll();
        if (!$countdowns) {
            throw CountdownException::failedToFetch();
        }
        $this->logger->debug('Fetched all countdowns');
        return $countdowns;
    }
}
