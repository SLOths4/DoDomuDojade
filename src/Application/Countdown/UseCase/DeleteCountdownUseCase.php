<?php
declare(strict_types=1);

namespace App\Application\Countdown\UseCase;

use App\Domain\Countdown\CountdownException;
use App\Domain\Event\EventPublisher;
use App\Infrastructure\Helper\CountdownValidationHelper;
use App\Infrastructure\Persistence\PDOCountdownRepository;
use App\Domain\Countdown\CountdownBusinessValidator;
use App\Domain\Countdown\CountdownRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Deletes specified countdown
 */
readonly class DeleteCountdownUseCase
{
    /**
     * @param CountdownRepositoryInterface $repository
     * @param LoggerInterface $logger
     * @param CountdownBusinessValidator $validator
     */
    public function __construct(
        private EventPublisher            $eventPublisher,
        private CountdownRepositoryInterface    $repository,
        private LoggerInterface           $logger,
        private CountdownBusinessValidator $validator,
    ){}

    /**
     * @param int $id
     * @return bool
     * @throws CountdownException
     */
    public function execute(int $id): bool
    {
        $this->logger->info('Deleting countdown',
            [
                'countdown_id' => $id
            ]
        );

        $this->validator->validateId($id);

        $countdown = $this->repository->findById($id);
        if ($countdown === null) {
            throw CountdownException::notFound($id);
        }

        $result = $this->repository->delete($id);

        if (!$result) {
            throw CountdownException::failedToDelete();
        }

        $countdown->markDeleted();
        $events = $countdown->getDomainEvents();
        $this->eventPublisher->publishAll($events);
        $countdown->clearDomainEvents();

        $this->logger->info('Countdown delete finished',
            [
                'countdown_id' => $id,
                'success' => true
            ]
        );
        return true;
    }
}
