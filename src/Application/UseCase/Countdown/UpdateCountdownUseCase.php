<?php
declare(strict_types=1);

namespace App\Application\UseCase\Countdown;

use App\Domain\Entity\Countdown;
use App\Infrastructure\Repository\CountdownRepository;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

readonly class UpdateCountdownUseCase
{
    public function __construct(
        private CountdownRepository $repository,
        private LoggerInterface $logger,
        private int $maxTitleLength
    ) {}

    /**
     * @throws Exception
     */
    public function execute(int $id, array $data): bool
    {
        $this->logger->info('Updating countdown', [
            'countdown_id' => $id,
            'payload_keys' => array_keys($data),
        ]);

        $this->validate($data);

        $existing = $this->repository->findById($id);

        if ($existing === null) {
            $this->logger->warning('Countdown not found for update', ['countdown_id' => $id]);
            throw new Exception("Countdown with ID $id not found");
        }

        $updatedData = [
            'title' => isset($data['title']) ? trim($data['title']) : $existing->title,
            'count_to' => isset($data['count_to'])
                ? new DateTimeImmutable($data['count_to'])
                : $existing->countTo,
        ];

        $updated = new Countdown(
            $existing->id,
            $updatedData['title'],
            $updatedData['count_to'],
            $existing->userId
        );

        $result = $this->repository->update($updated);

        $this->logger->info('Countdown update finished', [
            'countdown_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * @throws Exception
     */
    private function validate(array $data): void
    {
        if (isset($data['title']) && strlen($data['title']) > $this->maxTitleLength) {
            throw new Exception('Title too long');
        }
    }
}
