<?php
declare(strict_types=1);

namespace App\Application\Countdown\UseCase;

use App\Domain\Countdown\Countdown;
use App\Domain\Countdown\CountdownException;
use App\Infrastructure\Helper\CountdownValidationHelper;
use App\Infrastructure\Persistence\PDOCountdownRepository;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Fetches countdown by provided id
 */
readonly class GetCountdownByIdUseCase
{
    /**
     * @param PDOCountdownRepository $repository
     * @param LoggerInterface $logger
     * @param CountdownValidationHelper $validator
     */
    public function __construct(
        private PDOCountdownRepository    $repository,
        private LoggerInterface           $logger,
        private CountdownValidationHelper $validator,
    ) {}

    /**
     * @param int $id
     * @return Countdown|null
     * @throws CountdownException
     */
    public function execute(int $id): ?Countdown
    {
        $this->logger->debug('Fetching countdown by id', ['countdown_id' => $id]);
        $this->validator->validateId($id);
        $countdown = $this->repository->findById($id);
        if (!$countdown) {
            throw CountdownException::failedToFetch();
        }
        $this->logger->debug('Fetched countdown by id', ['countdown_id' => $id]);
        return $countdown;
    }
}
