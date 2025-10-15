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
    ) {}

    /**
     * Creates new countdown after validation.
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
        $countdown = $this->repo->findById($id);
        if (!$countdown) {
            throw new Exception("Countdown with ID $id not found");
        }
        foreach ($this->ALLOWED_FIELDS as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                if ($field === 'count_to') {
                    $countdown->$field = new DateTimeImmutable($value);
                } else {
                    $countdown->$field = trim($value);
                }
            }
        }
        return $this->repo->update($countdown);
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
        if (strlen($data['title'] ?? '') > $this->MAX_TITLE_LENGTH) {
            throw new Exception('Title too long');
        }
        if (empty($data['title'])) {
            throw new Exception('Missing title');
        }
        if (empty($data['count_to'])) {
            throw new Exception('Missing countdown date');
        }
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['count_to']);
        if (!$dt) {
            throw new Exception('Invalid countdown date format');
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
