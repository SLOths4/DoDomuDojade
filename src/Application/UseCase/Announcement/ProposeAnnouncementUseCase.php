<?php
declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\config\Config;
use App\Domain\Announcement;
use App\Infrastructure\Repository\AnnouncementRepository;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

readonly class ProposeAnnouncementUseCase
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
    public function execute(array $data): bool
    {
        $this->logger->info('Executing ProposeAnnouncementUseCase', [
            'payload_keys' => array_keys($data)
        ]);

        $this->validate($data);

        $validUntil = !empty($data['valid_until'])
            ? new DateTimeImmutable($data['valid_until'])
            : new DateTimeImmutable()->modify('+30 days');

        $announcement = Announcement::proposeNew(
            title: trim($data['title']),
            text: trim($data['text']),
            validUntil: $validUntil
        );

        $result = $this->repository->add($announcement);

        if ($result <= 0) {
            throw new InvalidArgumentException('announcement.failed_to_save');
        }

        $this->logger->info('Announcement proposed successfully', [
            'valid_until' => $validUntil->format('Y-m-d')
        ]);

        return $result;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validate(array $data): void
    {
        $title = trim($data['title'] ?? '');
        $text = trim($data['text'] ?? '');

        $minTitleLength = $this->config->announcementMinTitleLength;
        $maxTitleLength = $this->config->announcementMaxTitleLength;
        $minTextLength = $this->config->announcementMinTextLength;
        $maxTextLength = $this->config->announcementMaxTextLength;

        if (empty($title)) {
            throw new InvalidArgumentException('announcement.title_required');
        }

        if (empty($text)) {
            throw new InvalidArgumentException('announcement.text_required');
        }

        $titleLength = mb_strlen($title);
        if ($titleLength < $minTitleLength) {
            throw new InvalidArgumentException('announcement.title_too_short');
        }

        if ($titleLength > $maxTitleLength) {
            throw new InvalidArgumentException('announcement.title_too_long');
        }

        $textLength = mb_strlen($text);
        if ($textLength < $minTextLength) {
            throw new InvalidArgumentException('announcement.text_too_short');
        }

        if ($textLength > $maxTextLength) {
            throw new InvalidArgumentException('announcement.text_too_long');
        }

        if (!empty($data['valid_until'])) {
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
}
