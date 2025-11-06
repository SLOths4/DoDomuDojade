<?php

namespace src\service;

use src\entities\Countdown;
use src\repository\CountdownRepository;
use DateTimeImmutable;
use Exception;

readonly class CountdownService
{
    public function __construct(
        private CountdownRepository $repo,
        private int $MAX_TITLE_LENGTH,
        private array $ALLOWED_FIELDS,
        private string $DATE_FORMAT
    ) {}

    /**
     * Creates a new countdown after validation.
     * @param array $data
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function create(array $data, int $userId): bool
    {
        $this->validate($data);

        $countdown = new Countdown(
            null,
            trim($data['title']),
            new DateTimeImmutable($data['count_to']),
            $userId
        );
        return $this->repo->add($countdown);
    }

    /**
     * Updates an existing countdown.
     * @param int $id
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function update(int $id, array $data): bool
    {
        $this->validate($data);
        
        $existing = $this->repo->findById($id);
        
        if ($existing === null) {
            throw new Exception("Countdown with ID {$id} not found");
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
        
        return $this->repo->update($updated);
    }

    /**
     * Deletes a countdown by ID.
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }

    /**
     * Validation logic for countdown data.
     * @param array $data
     * @return void
     * @throws Exception
     */
    private function validate(array $data): void
    {
        if (empty($data['title'])) {
            throw new Exception('Missing title');
        }
        if (strlen($data['title']) > $this->MAX_TITLE_LENGTH) {
            throw new Exception('Title too long');
        }
        if (empty($data['count_to'])) {
            throw new Exception('Missing countdown date');
        }
    }

    /**
     * @throws Exception
     */
    public function getById(int $id): ?Countdown
    {
        return $this->repo->findById($id);
    }

    /**
     * @throws Exception
     */
    public function getCurrent(): ?Countdown
    {
        return $this->repo->findCurrent();
    }

    /**
     * @throws Exception
     */
    public function getAll(): array
    {
        return $this->repo->findAll();
    }
}
