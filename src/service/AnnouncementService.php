<?php

namespace src\service;

use src\repository\AnnouncementRepository;
use src\entities\Announcement;
use DateTimeImmutable;
use Exception;

readonly class AnnouncementService {
    public function __construct(
        private AnnouncementRepository $repo,
        private int                    $MAX_TITLE_LENGTH,
        private int                    $MAX_TEXT_LENGTH,
        private array                  $ALLOWED_FIELDS,
    ) {}

    /**
     * Adds validated announcement
     * @param array $data
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function create(array $data, int $userId): bool
    {
        $this->validate($data);
        $a = new Announcement(
            null,
            trim($data['title']),
            trim($data['text']),
            new DateTimeImmutable(),
            new DateTimeImmutable($data['valid_until']),
            $userId
        );
        return $this->repo->add($a);
    }

    public function update(int $id, array $data): bool 
    {
        $this->validate($data);
        
        $existing = $this->repo->findById($id);

        if ($existing === null) {
            throw new Exception("Announcement with ID {$id} not found");
        }
        
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
        
        return $this->repo->update($updated);
    }

    /**
     * Deletes
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delete(int $id): bool {
        return$this->repo->delete($id);
    }

    /**
     * Validation logic
     * @param array $d
     * @return void
     * @throws Exception
     */
    private function validate(array $d): void {
        if (strlen($d['title'] ?? '') > $this->MAX_TITLE_LENGTH)
            throw new Exception('Title too long');
        if (strlen($d['text'] ?? '') > $this->MAX_TEXT_LENGTH)
            throw new Exception('Text too long');
        if (empty($d['valid_until']))
            throw new Exception('Missing expiration date');
    }

    /**
     * Returns all valid announcements
     * @return Announcement[]
     * @throws Exception
     */
    public function getValid(): array { return $this->repo->findValid(); }

    /**
     * Returns all announcements
     * @return Announcement[]
     * @throws Exception
     */
    public function getAll(): array { return $this->repo->findAll(); }

    /**
     * Returns announcement by id
     * @param int $id
     * @return Announcement
     * @throws Exception
     */
    public function getById(int $id): Announcement { return $this->repo->findById($id); }
}
