<?php
declare(strict_types=1);

namespace App\Application\UseCase\Countdown;

use App\Domain\Countdown;
use App\Infrastructure\Repository\CountdownRepository;
use Psr\Log\LoggerInterface;
use Exception;

readonly class GetCountdownByIdUseCase
{
    public function __construct(
        private CountdownRepository $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(int $id): ?Countdown
    {
        $this->logger->debug('Fetching countdown by id', ['countdown_id' => $id]);
        $countdown = $this->repository->findById($id);
        $this->logger->debug('Fetched countdown by id', ['countdown_id' => $id, 'found' => $countdown !== null]);
        return $countdown;
    }
}
