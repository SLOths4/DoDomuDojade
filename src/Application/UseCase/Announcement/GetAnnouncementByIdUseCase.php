<?php
declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\Domain\Entity\Announcement;
use App\Infrastructure\Repository\AnnouncementRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class GetAnnouncementByIdUseCase
{
    public function __construct(
        private AnnouncementRepository $repository,
        private LoggerInterface        $logger
    )
    {
    }

    /**
     * @param int $id
     * @return Announcement
     * @throws Exception
     */
    public function execute(int $id): Announcement
    {
        $this->logger->debug('Executing GetAnnouncementByIdUseCase');
        return $this->repository->findById($id);
    }
}
