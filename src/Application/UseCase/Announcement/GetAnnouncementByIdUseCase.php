<?php
declare(strict_types=1);

namespace App\Application\UseCase\Announcement;

use App\Domain\Entity\Announcement;
use App\Domain\Exception\AnnouncementException;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Repository\AnnouncementRepository;
use Exception;
use Psr\Log\LoggerInterface;

readonly class GetAnnouncementByIdUseCase
{
    public function __construct(
        private AnnouncementRepository $repository,
        private LoggerInterface        $logger,
        private AnnouncementValidationHelper $validator,
    ){}

    /**
     * @param int $id
     * @return Announcement
     * @throws Exception
     */
    public function execute(int $id): Announcement
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
