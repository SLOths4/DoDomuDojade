<?php
declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\Domain\Announcement;
use App\Infrastructure\Repository\AnnouncementRepository;
use App\config\Config;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

readonly class CreateAnnouncementUseCase
{
    public function __construct(
        private AnnouncementRepository $repository,
        private Config $config,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(array $data, int $userId): bool
    {
        $this->logger->info('Executing CreateAnnouncementUseCase', [
            'user_id' => $userId,
            'payload_keys' => array_keys($data)
        ]);

        $this->validate($data);

        $announcement = new Announcement(
            null,
            trim($data['title']),
            trim($data['text']),
            new DateTimeImmutable(),
            new DateTimeImmutable($data['valid_until']),
            $userId
        );

        $result = $this->repository->add($announcement);

        $this->logger->info('Announcement created successfully', [
            'user_id' => $userId,
            'success' => $result
        ]);

        return $result;
    }

    /**
     * @throws Exception
     */
    private function validate(array $data): void
    {
        if (mb_strlen($data['title'] ?? '') > $this->config->announcementMaxTitleLength) {
            throw new Exception('Title too long');
        }

        if (mb_strlen($data['text'] ?? '') > $this->config->announcementMaxTextLength) {
            throw new Exception('Text too long');
        }

        if (empty($data['valid_until'])) {
            throw new Exception('Missing expiration date');
        }
    }
}
