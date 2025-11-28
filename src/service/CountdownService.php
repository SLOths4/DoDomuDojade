<?php

namespace src\service;

use src\entities\Countdown;
use src\repository\CountdownRepository;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

readonly class CountdownService
{
    public function __construct(
        private CountdownRepository $repo,
        private int                 $MAX_TITLE_LENGTH,
        private string              $DATE_FORMAT,
        private LoggerInterface     $logger,
    ) {}

    /**
     * Creates a new countdown after validation.
     * @param array $data
     * @param int $userId
     * @return bool success
     * @throws Exception
     */
    public function create(array $data, int $userId): bool
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

        $result = $this->repo->add($countdown);

        $this->logger->info('Countdown creation finished', [
            'user_id' => $userId,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * Updates an existing countdown.
     * @param int $id
     * @param array $data
     * @return bool success
     * @throws Exception
     */
    public function update(int $id, array $data): bool
    {
        $this->logger->info('Updating countdown', [
            'countdown_id' => $id,
            'payload_keys' => array_keys($data),
        ]);

        $this->validate($data);

        $existing = $this->repo->findById($id);

        if ($existing === null) {
            $this->logger->warning('Countdown not found for update', [
                'countdown_id' => $id,
            ]);
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

        $result = $this->repo->update($updated);

        $this->logger->info('Countdown update finished', [
            'countdown_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * Deletes a countdown by ID.
     * @param int $id
     * @return bool success
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        $this->logger->info('Deleting countdown', [
            'countdown_id' => $id,
        ]);

        $result = $this->repo->delete($id);

        $this->logger->info('Countdown delete finished', [
            'countdown_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * Validates countdown data
     * @param array $data
     * @return void
     * @throws Exception
     */
    private function validate(array $data): void
    {
        $this->logger->debug('Validating countdown data', [
            'has_title' => array_key_exists('title', $data),
            'has_count_to' => !empty($data['count_to'] ?? null),
        ]);

        if (empty($data['title'])) {
            $this->logger->warning('Missing countdown title');
            throw new Exception('Missing title');
        }

        if (strlen($data['title']) > $this->MAX_TITLE_LENGTH) {
            $this->logger->warning('Countdown title is too long', [
                'length' => strlen($data['title']),
                'max_length' => $this->MAX_TITLE_LENGTH,
            ]);
            throw new Exception('Title too long');
        }

        if (empty($data['count_to'])) {
            $this->logger->warning('Missing countdown date');
            throw new Exception('Missing countdown date');
        }

        $this->logger->debug('Countdown data validation passed');
    }

    /**
     * Returns countdown by id
     * @param int $id
     * @return ?Countdown
     * @throws Exception
     */
    public function getById(int $id): ?Countdown
    {
        $this->logger->debug('Fetching countdown by id', [
            'countdown_id' => $id,
        ]);

        $countdown = $this->repo->findById($id);

        $this->logger->debug('Fetched countdown by id', [
            'countdown_id' => $id,
            'found' => $countdown !== null,
        ]);

        return $countdown;
    }

    /**
     * Returns current countdown
     * @return ?Countdown
     * @throws Exception
     */
    public function getCurrent(): ?Countdown
    {
        $this->logger->debug('Fetching current countdown');

        $countdown = $this->repo->findCurrent();

        $this->logger->debug('Fetched current countdown', [
            'found' => $countdown !== null,
        ]);

        return $countdown;
    }

    /**
     * Returns all countdowns
     * @return Countdown[]
     * @throws Exception
     */
    public function getAll(): array
    {
        $this->logger->debug('Fetching all countdowns');

        $countdowns = $this->repo->findAll();

        $this->logger->debug('Fetched all countdowns', [
            'count' => count($countdowns),
        ]);

        return $countdowns;
    }
}