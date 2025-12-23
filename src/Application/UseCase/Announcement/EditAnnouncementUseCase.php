<?php
declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\config\Config;
use App\Domain\Entity\Announcement;
use App\Domain\Enum\AnnouncementStatus;
use App\Infrastructure\Repository\AnnouncementRepository;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

readonly class EditAnnouncementUseCase
{
    public function __construct(
        private AnnouncementRepository $repository,
        private Config                 $config,
        private LoggerInterface        $logger
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function execute(int $id, array $data, int $adminId): int
    {
        $this->logger->info('Executing EditAnnouncementUseCase', [
            'announcement_id' => $id,
            'payload_keys' => array_keys($data)
        ]);

        $existing = $this->repository->findById($id);

        $this->validate($data);

        $newStatus = isset($data['status'])
            ? $this->stringToStatus($data['status'])
            : $existing->status;

        $isStatusChanged = $newStatus !== $existing->status;

        $updated = new Announcement(
            id: $existing->id,
            title: isset($data['title']) ? trim($data['title']) : $existing->title,
            text: isset($data['text']) ? trim($data['text']) : $existing->text,
            createdAt: $existing->createdAt,
            validUntil: isset($data['valid_until'])
                ? new DateTimeImmutable($data['valid_until'])
                : $existing->validUntil,
            userId: $existing->userId,
            status: $newStatus,
            decidedAt: $isStatusChanged ? new DateTimeImmutable() : $existing->decidedAt,
            decidedBy: $isStatusChanged ? $adminId : $existing->decidedBy,
        );

        $result = $this->repository->update($updated);

        if (!$result) {
            throw new InvalidArgumentException('announcement.update_failed');
        }

        $this->logger->info('Announcement updated successfully', [
            'announcement_id' => $id,
        ]);

        return $id;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validate(array $data): void
    {
        $maxTitleLength = $this->config->announcementMaxTitleLength ?? 255;
        $maxTextLength = $this->config->announcementMaxTextLength ?? 5000;

        if (isset($data['title'])) {
            $title = trim($data['title']);

            if (empty($title)) {
                throw new InvalidArgumentException('announcement.title_required');
            }

            if (mb_strlen($title) > $maxTitleLength) {
                throw new InvalidArgumentException('announcement.title_too_long');
            }
        }

        if (isset($data['text'])) {
            $text = trim($data['text']);

            if (empty($text)) {
                throw new InvalidArgumentException('announcement.text_required');
            }

            if (mb_strlen($text) > $maxTextLength) {
                throw new InvalidArgumentException('announcement.text_too_long');
            }
        }

        if (isset($data['valid_until'])) {
            try {
                $validUntil = new DateTimeImmutable($data['valid_until']);
                $today = new DateTimeImmutable();

                if ($validUntil < $today) {
                    throw new InvalidArgumentException('announcement.expiration_date_in_past');
                }
            } catch (Exception $e) {
                throw new InvalidArgumentException('announcement.invalid_date_format');
            }
        }
    }

    private function stringToStatus(string|int $status): AnnouncementStatus
    {
        $statusMap = [
            0 => AnnouncementStatus::PENDING,
            1 => AnnouncementStatus::APPROVED,
            2 => AnnouncementStatus::REJECTED,
            'PENDING' => AnnouncementStatus::PENDING,
            'APPROVED' => AnnouncementStatus::APPROVED,
            'REJECTED' => AnnouncementStatus::REJECTED,
        ];

        $key = (string)$status;

        if (!isset($statusMap[$key]) && !isset($statusMap[(int)$status])) {
            throw new InvalidArgumentException('announcement.invalid_status');
        }

        return $statusMap[$key] ?? $statusMap[(int)$status];
    }

}
