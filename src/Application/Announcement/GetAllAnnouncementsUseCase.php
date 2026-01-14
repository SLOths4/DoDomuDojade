<?php
declare(strict_types=1);

namespace App\Application\Announcement;

use App\Domain\Announcement\Announcement;
use App\Infrastructure\Persistence\PDOAnnouncementRepository;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Fetches all available announcements
 */
readonly class GetAllAnnouncementsUseCase
{
    public function __construct(
        private PDOAnnouncementRepository $repository,
        private LoggerInterface           $logger
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
