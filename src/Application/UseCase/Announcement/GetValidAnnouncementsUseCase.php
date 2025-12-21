<?php
declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\Domain\Announcement;
use App\Infrastructure\Repository\AnnouncementRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class GetValidAnnouncementsUseCase
{
    public function __construct(
        private AnnouncementRepository $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * @return Announcement[]
     * @throws Exception
     */
    public function execute(): array
    {
        $this->logger->debug('Executing GetValidAnnouncementsUseCase');
        return $this->repository->findValid();
    }
}
