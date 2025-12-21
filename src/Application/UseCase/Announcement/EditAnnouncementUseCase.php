<?php
declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\Domain\Announcement;
use App\Infrastructure\Repository\AnnouncementRepository;
use App\config\Config;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

readonly class  EditAnnouncementUseCase
{
    public function __construct(
        private AnnouncementRepository $repository,
        private Config $config,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(int $id, array $data): bool
    {
        $this->logger->info('Executing EditAnnouncementUseCase', [
            'announcement_id' => $id,
            'payload_keys' => array_keys($data)
        ]);

        $this->validate($data);

        $existing = $this->repository->findById($id);

        $updated = new Announcement(
            $existing->id,
            isset($data['title']) ? trim($data['title']) : $existing->title,
            isset($data['text']) ? trim($data['text']) : $existing->text,
            $existing->date,
            isset($data['valid_until']) ? new DateTimeImmutable($data['valid_until']) : $existing->validUntil,
            $existing->userId
        );

        $result = $this->repository->update($updated);

        $this->logger->info('Announcement updated successfully', [
            'announcement_id' => $id,
            'success' => $result
        ]);

        return $result;
    }

    /**
     * @throws Exception
     */
    private function validate(array $data): void
    {
        if (isset($data['title']) && mb_strlen($data['title']) > $this->config->announcementMaxTitleLength) {
            throw new Exception('Title too long');
        }

        if (isset($data['text']) && mb_strlen($data['text']) > $this->config->announcementMaxTextLength) {
            throw new Exception('Text too long');
        }
    }
}
