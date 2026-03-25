<?php
declare(strict_types=1);

namespace App\Application\Announcement\UseCase;

use App\Infrastructure\Persistence\PDOAnnouncementRepository;
use App\Domain\Shared\InvalidDateTimeException;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

/**
 * Use case for deleting announcements since the provided date
 */
readonly class DeleteRejectedSinceAnnouncementUseCase
{
    /**
     * @param PDOAnnouncementRepository $repository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private PDOAnnouncementRepository $repository,
        private LoggerInterface           $logger,
    ){}

    /**
     * Deletes rejected announcements older than the specified date
     * @param string $date
     * @return int number of announcements deleted
     */
    public function execute(string $date): int
    {
        $this->logger->info('Executing DeleteRejectedSinceAnnouncementUseCase', ['older than' => $date]);

        $parsedDate = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if ($parsedDate === false) {
            throw new InvalidDateTimeException($date, 'date', 'Y-m-d');
        }

        $result = $this->repository->deleteRejectedOlderThan($parsedDate);

        $this->logger->info('Announcement deleted successfully', [
            'older than' => $parsedDate,
            'announcements removed' => $result
        ]);

        return $result;
    }
}
