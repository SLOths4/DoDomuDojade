<?php

namespace App\Http\Controller;

use App\Application\DataTransferObject\AddAnnouncementDTO;
use App\Application\DataTransferObject\EditAnnouncementDTO;
use App\Application\DataTransferObject\ProposeAnnouncementDTO;
use App\Application\UseCase\Announcement\ApproveRejectAnnouncementUseCase;
use App\Application\UseCase\Announcement\CreateAnnouncementUseCase;
use App\Application\UseCase\Announcement\DeleteAnnouncementUseCase;
use App\Application\UseCase\Announcement\EditAnnouncementUseCase;
use App\Application\UseCase\Announcement\ProposeAnnouncementUseCase;
use App\config\Config;
use App\Domain\Enum\AnnouncementStatus;
use App\Domain\Exception\AnnouncementException;
use App\Http\Context\RequestContext;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\View\ViewRendererInterface;
use DateTimeImmutable;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;


final class AnnouncementController extends BaseController
{
    public function __construct(
        readonly RequestContext $requestContext,
        readonly ViewRendererInterface $renderer,
        readonly FlashMessengerInterface $flash,
        private readonly LoggerInterface                    $logger,
        private readonly Config                             $config,
        private readonly CreateAnnouncementUseCase          $createAnnouncementUseCase,
        private readonly DeleteAnnouncementUseCase          $deleteAnnouncementUseCase,
        private readonly EditAnnouncementUseCase            $editAnnouncementUseCase,
        private readonly ProposeAnnouncementUseCase         $proposeAnnouncementUseCase,
        private readonly ApproveRejectAnnouncementUseCase   $approveRejectAnnouncementUseCase,
    ){}

    /**
     * @throws AnnouncementException
     * @throws Exception
     */
    #[NoReturn]
    public function deleteAnnouncement(): void
    {
        $this->logger->debug("Add announcement request received");
        $announcementId = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
        $this->deleteAnnouncementUseCase->execute($announcementId);
        $this->logger->debug("Add announcement request received");
        $this->flash('success', 'announcement.deleted_successfully');
        $this->redirect('/panel/announcements');
    }

    /**
     * @throws Exception
     */
    #[NoReturn]
    public function addAnnouncement(): void
    {
        $dto = AddAnnouncementDTO::fromHttpRequest($_POST);
        $userId = $this->getCurrentUserId();

        $this->createAnnouncementUseCase->execute(
            $dto,
            $userId
        );

        $this->flash('success', 'announcement.created_successfully');
        $this->redirect('/panel/announcements');
    }

    /**
     * @throws AnnouncementException
     * @throws Exception
     */
    #[NoReturn]
    public function editAnnouncement(): void
    {
        $id = (int)filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

        $dto = EditAnnouncementDTO::fromHttpRequest($_POST);
        $userId = $this->getCurrentUserId();

        $this->editAnnouncementUseCase->execute(
            $id,
            $dto,
            $userId
        );

        $this->flash('success', 'announcement.updated_successfully');
        $this->redirect('/panel/announcements');
    }

    /**
     * @throws Exception
     */
    #[NoReturn]
    public function approveAnnouncement(): void
    {
        $announcementId = (int)filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

        $userId = $this->getCurrentUserId();

        $this->approveRejectAnnouncementUseCase->execute(
            $announcementId,
            AnnouncementStatus::APPROVED,
            $userId
        );

        $this->flash('success', 'announcement.approved_successfully');
        $this->redirect('/panel/announcements');
    }

    /**
     * @throws Exception
     */
    #[NoReturn]
    public function rejectAnnouncement(): void
    {
        $announcementId = (int)filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

        $userId = $this->getCurrentUserId();
        $this->approveRejectAnnouncementUseCase->execute(
            $announcementId,
            AnnouncementStatus::REJECTED,
            $userId
        );

        $this->flash('success', 'announcement.rejected_successfully');
        $this->redirect('/panel/announcements');
    }


    /**
     * @throws Exception
     */
    #[NoReturn]
    public function proposeAnnouncement(): void
    {
        $today = new DateTimeImmutable();
        $modified = $today->modify($this->config->announcementDefaultValidDate);

        $dto = ProposeAnnouncementDTO::fromHttpRequest($_POST, $modified);

        $this->proposeAnnouncementUseCase->execute($dto);

        $this->flash('success', 'announcement.proposed_successfully');
        $this->redirect('/propose');
    }
}