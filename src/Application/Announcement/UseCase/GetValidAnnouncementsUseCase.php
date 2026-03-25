<?php
declare(strict_types=1);

namespace App\Application\Announcement\UseCase;

use App\Domain\Announcement\Announcement;
use App\Domain\Announcement\AnnouncementRepositoryInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Fetches all valid announcements
 */
readonly class GetValidAnnouncementsUseCase
{
    /**
     * @param AnnouncementRepositoryInterface $repository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private AnnouncementRepositoryInterface $repository,
        private LoggerInterface           $logger
    ) {}

    /**
     * Fetches all valid announcements
     * @return Announcement[]
     * @throws Exception
     */
    public function execute(): array
    {
        $this->logger->debug('Executing GetValidAnnouncementsUseCase');
        return $this->repository->findValid();
    }
}
