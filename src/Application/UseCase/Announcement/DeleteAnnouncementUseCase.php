<?php
declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\Infrastructure\Repository\AnnouncementRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class DeleteAnnouncementUseCase
{
    public function __construct(
        private AnnouncementRepository $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws Exception
     */
    public function execute(int $id): bool
    {
        $this->logger->info('Executing DeleteAnnouncementUseCase', ['announcement_id' => $id]);

        $result = $this->repository->delete($id);

        $this->logger->info('Announcement deleted successfully', [
            'announcement_id' => $id,
            'success' => $result
        ]);

        return $result;
    }
}
