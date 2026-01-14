<?php
declare(strict_types=1);

namespace App\Application\Countdown;

use App\Domain\Countdown\Countdown;
use App\Domain\Exception\CountdownException;
use App\Infrastructure\Persistence\CountdownRepository;
use Exception;
use Psr\Log\LoggerInterface;

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
        if (!$countdown) {
            throw CountdownException::failedToFetch();
        }
        $this->logger->debug('Fetched current countdown');
        return $countdown;
    }
}
