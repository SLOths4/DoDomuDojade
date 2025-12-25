<?php
declare(strict_types=1);

namespace App\Application\UseCase\Countdown;

use App\Domain\Exception\CountdownException;
use App\Infrastructure\Helper\CountdownValidationHelper;
use App\Infrastructure\Repository\CountdownRepository;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Deletes specified countdown
 */
readonly class DeleteCountdownUseCase
{
    public function __construct(
        private CountdownRepository $repository,
        private LoggerInterface $logger,
        private CountdownValidationHelper $validator,
    ){}

    /**
     * @throws Exception
     */
    public function execute(int $id): bool
    {
        $this->logger->info('Deleting countdown',
            [
                'countdown_id' => $id
            ]
        );

        $this->validator->validateId($id);

        $result = $this->repository->delete($id);

        if (!$result) {
            throw CountdownException::failedToDelete();
        }

        $this->logger->info('Countdown delete finished',
            [
                'countdown_id' => $id,
                'success' => true
            ]
        );
        return true;
    }
}
