<?php
declare(strict_types=1);

namespace App\Application\UseCase\Countdown;

use App\Infrastructure\Repository\CountdownRepository;
use Psr\Log\LoggerInterface;
use Exception;

readonly class DeleteCountdownUseCase
{
    public function __construct(
        private CountdownRepository $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(int $id): bool
    {
        $this->logger->info('Deleting countdown', ['countdown_id' => $id]);
        $result = $this->repository->delete($id);
        $this->logger->info('Countdown delete finished', ['countdown_id' => $id, 'success' => $result]);
        return $result;
    }
}
