<?php
declare(strict_types=1);

namespace App\Application\UseCase\Countdown;

use App\Domain\Countdown;
use App\Infrastructure\Repository\CountdownRepository;
use Psr\Log\LoggerInterface;
use Exception;

readonly class GetCurrentCountdownUseCase
{
    public function __construct(
        private CountdownRepository $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(): ?Countdown
    {
        $this->logger->debug('Fetching current countdown');
        $countdown = $this->repository->findCurrent();
        $this->logger->debug('Fetched current countdown', ['found' => $countdown !== null]);
        return $countdown;
    }
}
