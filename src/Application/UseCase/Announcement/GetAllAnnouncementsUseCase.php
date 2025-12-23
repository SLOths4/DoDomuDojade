<?php
declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\Domain\Entity\Announcement;
use App\Infrastructure\Repository\AnnouncementRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class GetAllAnnouncementsUseCase
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
        $this->logger->debug('Executing GetAllAnnouncementsUseCase');
        return $this->repository->findAll();
    }
}
