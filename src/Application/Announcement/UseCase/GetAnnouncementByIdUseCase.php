<?php
declare(strict_types=1);

namespace App\Application\Announcement\UseCase;

use App\Domain\Announcement\Announcement;
use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementId;
use App\Domain\Announcement\AnnouncementBusinessValidator;
use App\Domain\Announcement\AnnouncementRepositoryInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Fetches an announcement by provided id
 */
readonly class GetAnnouncementByIdUseCase
{
    /**
     * @param AnnouncementRepositoryInterface $repository
     * @param LoggerInterface $logger
     * @param AnnouncementBusinessValidator $validator
     */
    public function __construct(
        private AnnouncementRepositoryInterface    $repository,
        private LoggerInterface              $logger,
        private AnnouncementBusinessValidator $validator,
    ){}

    /**
     * @param AnnouncementId $id
     * @return Announcement
     * @throws Exception
     */
    public function execute(AnnouncementId $id): Announcement
    {
        $this->logger->debug('Fetching announcement by id', ['announcement_id' => $id]);
        $this->validator->validateId($id);
        $result = $this->repository->findById($id);
        if (!$result) {
            throw  AnnouncementException::notFound($id);
        }
        $this->logger->debug("Fetched announcement by id", ['announcement_id' => $id]);
        return $result;
    }
}
