<?php
declare(strict_types=1);

namespace App\Application\UseCase\Countdown;

use App\Infrastructure\Repository\CountdownRepository;
use Psr\Log\LoggerInterface;
use Exception;

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
        $this->logger->debug('Fetched all countdowns', ['count' => count($countdowns)]);
        return $countdowns;
    }
}
