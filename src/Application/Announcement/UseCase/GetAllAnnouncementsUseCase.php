<?php
declare(strict_types=1);

namespace App\Application\Announcement\UseCase;

use App\Domain\Announcement\Announcement;
use App\Domain\Announcement\AnnouncementRepositoryInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Fetches all available announcements
 */
readonly class GetAllAnnouncementsUseCase
{
    /**
     * @param AnnouncementRepositoryInterface $repository
     * @param LoggerInterface                 $logger
     */
    public function __construct(
        private AnnouncementRepositoryInterface $repository,
        private LoggerInterface                 $logger
    ){}

    /**
     * Fetches all announcements
     * @return Announcement[]
     * @throws Exception
     */
    public function execute(): array
    {
        $this->logger->debug('Fetching all announcements');
        $result = $this->repository->findAll();
        $this->logger->debug('Fetched all announcements');
        return $result;
    }
}
