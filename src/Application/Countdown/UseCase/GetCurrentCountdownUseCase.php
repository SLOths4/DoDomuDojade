<?php
declare(strict_types=1);

namespace App\Application\Countdown\UseCase;

use App\Domain\Countdown\Countdown;
use App\Infrastructure\Persistence\PDOCountdownRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class GetCurrentCountdownUseCase
{
    public function __construct(
        private PDOCountdownRepository $repository,
        private LoggerInterface        $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(): ?Countdown
    {
        $this->logger->debug('Fetching current countdown');
        return $this->repository->findCurrent();
    }
}
