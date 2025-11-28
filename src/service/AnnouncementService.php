<?php

namespace src\service;

use src\repository\AnnouncementRepository;
use src\entities\Announcement;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

readonly class AnnouncementService
{
    public function __construct(
        private AnnouncementRepository $repo,
        private int                    $MAX_TITLE_LENGTH,
        private int                    $MAX_TEXT_LENGTH,
        private LoggerInterface        $logger,
    ) {}

    /**
     * Adds validated announcement
     * @param array $data
     * @param int $userId
     * @return bool success
     * @throws Exception
     */
    public function create(array $data, int $userId): bool
    {
        $this->logger->info('Creating announcement', [
            'user_id' => $userId,
            'payload_keys' => array_keys($data),
        ]);

        $this->validate($data);

        $a = new Announcement(
            null,
            trim($data['title']),
            trim($data['text']),
            new DateTimeImmutable(),
            new DateTimeImmutable($data['valid_until']),
            $userId
        );

        $result = $this->repo->add($a);

        $this->logger->info('Announcement creation finished', [
            'user_id' => $userId,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * Updates announcement with provided data
     * @param int $id
     * @param array $data
     * @return bool success
     * @throws Exception
     */
    public function update(int $id, array $data): bool
    {
        $this->logger->info('Updating announcement', [
            'announcement_id' => $id,
            'payload_keys' => array_keys($data),
        ]);

        $this->validate($data);

        $existing = $this->repo->findById($id);

        $updatedData = [
            'title' => isset($data['title']) ? trim($data['title']) : $existing->title,
            'text' => isset($data['text']) ? trim($data['text']) : $existing->text,
            'valid_until' => isset($data['valid_until'])
                ? new DateTimeImmutable($data['valid_until'])
                : $existing->validUntil,
        ];

        $updated = new Announcement(
            $existing->id,
            $updatedData['title'],
            $updatedData['text'],
            $existing->date,
            $updatedData['valid_until'],
            $existing->userId
        );

        $result = $this->repo->update($updated);

        $this->logger->info('Announcement update finished', [
            'announcement_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * Deletes announcement
     * @param int $id
     * @return bool success
     * @throws Exception
     */
    public function delete(int $id): bool {
        $this->logger->info('Deleting announcement', [
            'announcement_id' => $id,
        ]);

        $result = $this->repo->delete($id);

        $this->logger->info('Announcement delete finished', [
            'announcement_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * Validates provided data
     * @param array $data
     * @return void
     * @throws Exception
     */
    private function validate(array $data): void {
        $this->logger->debug('Validating announcement data', [
            'has_title' => array_key_exists('title', $data),
            'has_text' => array_key_exists('text', $data),
            'has_valid_until' => !empty($data['valid_until'] ?? null),
        ]);

        if (strlen($data['title'] ?? '') > $this->MAX_TITLE_LENGTH) {
            $this->logger->warning('Announcement title is too long', [
                'length' => strlen($data['title'] ?? ''),
                'max_length' => $this->MAX_TITLE_LENGTH,
            ]);
            throw new Exception('Title too long');
        }

        if (strlen($data['text'] ?? '') > $this->MAX_TEXT_LENGTH) {
            $this->logger->warning('Announcement text is too long', [
                'length' => strlen($data['text'] ?? ''),
                'max_length' => $this->MAX_TEXT_LENGTH,
            ]);
            throw new Exception('Text too long');
        }

        if (empty($data['valid_until'])) {
            $this->logger->warning('Missing expiration date for announcement');
            throw new Exception('Missing expiration date');
        }

        $this->logger->debug('Announcement data validation passed');
    }

    /**
     * Returns all valid announcements
     * @return Announcement[]
     * @throws Exception
     */
    public function getValid(): array {
        $this->logger->debug('Fetching valid announcements');
        $announcements = $this->repo->findValid();
        $this->logger->debug('Fetched valid announcements', [
            'count' => count($announcements),
        ]);

        return $announcements;
    }

    /**
     * Returns all announcements
     * @return Announcement[]
     * @throws Exception
     */
    public function getAll(): array {
        $this->logger->debug('Fetching all announcements');
        $announcements = $this->repo->findAll();
        $this->logger->debug('Fetched all announcements', [
            'count' => count($announcements),
        ]);

        return $announcements;
    }

    /**
     * Returns announcement by id
     * @param int $id
     * @return Announcement
     * @throws Exception
     */
    public function getById(int $id): Announcement {
        $this->logger->debug('Fetching announcement by id', [
            'announcement_id' => $id,
        ]);

        $announcement = $this->repo->findById($id);

        $this->logger->debug('Fetched announcement by id', [
            'announcement_id' => $id,
        ]);

        return $announcement;
    }
}