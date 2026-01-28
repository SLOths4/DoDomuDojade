<?php
declare(strict_types=1);

namespace App\Application\Announcement\UseCase;

use App\Infrastructure\Persistence\PDOAnnouncementRepository;
use DateTimeImmutable;
use Exception;
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
     * @throws Exception
     */
    public function execute(string $date): int
    {
        $this->logger->info('Executing DeleteRejectedSinceAnnouncementUseCase', ['older than' => $date]);

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if ($date === false) {
            throw new Exception('Error parsing data');
        }

        $result = $this->repository->deleteRejectedOlderThan($date);

        $this->logger->info('Announcement deleted successfully', [
            'older than' => $date,
            'announcements removed' => $result
        ]);

        return $result;
    }
}
