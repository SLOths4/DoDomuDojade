<?php
declare(strict_types=1);

namespace App\Application\UseCase\Countdown;

use App\Domain\Countdown;
use App\Infrastructure\Repository\CountdownRepository;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use Exception;

readonly class CreateCountdownUseCase
{
    public function __construct(
        private CountdownRepository $repository,
        private LoggerInterface $logger,
        private int $maxTitleLength
    ) {}

    /**
     * @throws Exception
     */
    public function execute(array $data, int $userId): bool
    {
        $this->logger->info('Creating countdown', [
            'user_id' => $userId,
            'payload_keys' => array_keys($data),
        ]);

        $this->validate($data);

        $countdown = new Countdown(
            null,
            trim($data['title']),
            new DateTimeImmutable($data['count_to']),
            $userId
        );

        $result = $this->repository->add($countdown);

        $this->logger->info('Countdown creation finished', [
            'user_id' => $userId,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * @throws Exception
     */
    private function validate(array $data): void
    {
        if (empty($data['title'])) {
            throw new Exception('Missing title');
        }

        if (strlen($data['title']) > $this->maxTitleLength) {
            throw new Exception('Title too long');
        }

        if (empty($data['count_to'])) {
            throw new Exception('Missing countdown date');
        }
    }
}
