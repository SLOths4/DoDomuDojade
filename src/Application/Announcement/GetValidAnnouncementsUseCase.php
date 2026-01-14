<?php
declare(strict_types=1);

namespace App\Application\Announcement;

use App\Domain\Announcement\Announcement;
use App\Infrastructure\Persistence\PDOAnnouncementRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class GetValidAnnouncementsUseCase
{
    public function __construct(
        private PDOAnnouncementRepository $repository,
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
