<?php

namespace App\Http\Controller;

use App\Application\Announcement\AddAnnouncementDTO;
use App\Application\Announcement\ApproveRejectAnnouncementUseCase;
use App\Application\Announcement\CreateAnnouncementUseCase;
use App\Application\Announcement\DeleteAnnouncementUseCase;
use App\Application\Announcement\EditAnnouncementDTO;
use App\Application\Announcement\EditAnnouncementUseCase;
use App\Application\Announcement\ProposeAnnouncementDTO;
use App\Application\Announcement\ProposeAnnouncementUseCase;
use App\config\Config;
use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementStatus;
use App\Http\Context\RequestContext;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\View\ViewRendererInterface;
use DateTimeImmutable;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use Predis\Client;


use Psr\Http\Message\ResponseInterface;


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
        private readonly Client                             $redis,
    ){}

    /**
     * @throws AnnouncementException
     * @throws Exception
     */
    public function deleteAnnouncement(): ResponseInterface
    {
        $this->logger->debug("Delete announcement request received");
        $announcementId = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
        $this->deleteAnnouncementUseCase->execute($announcementId);

        $this->flash('success', 'announcement.deleted_successfully');
        return $this->redirect('/panel/announcements');
    }

    /**
     * @throws Exception
     */
    public function addAnnouncement(): ResponseInterface
    {
        $dto = AddAnnouncementDTO::fromHttpRequest($_POST);
        $userId = $this->getCurrentUserId();

        $this->createAnnouncementUseCase->execute(
            $dto,
            $userId
        );

        $this->flash('success', 'announcement.created_successfully');
        return $this->redirect('/panel/announcements');
    }

    /**
     * @throws AnnouncementException
     * @throws Exception
     */
    public function editAnnouncement(): ResponseInterface
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
        return $this->redirect('/panel/announcements');
    }

    /**
     * @throws Exception
     */
    public function approveAnnouncement(): ResponseInterface
    {
        $announcementId = (int)filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

        $userId = $this->getCurrentUserId();

        $this->approveRejectAnnouncementUseCase->execute(
            $announcementId,
            AnnouncementStatus::APPROVED,
            $userId
        );

        $this->flash('success', 'announcement.approved_successfully');
        return $this->redirect('/panel/announcements');
    }

    /**
     * @throws Exception
     */
    public function rejectAnnouncement(): ResponseInterface
    {
        $announcementId = (int)filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

        $userId = $this->getCurrentUserId();
        $this->approveRejectAnnouncementUseCase->execute(
            $announcementId,
            AnnouncementStatus::REJECTED,
            $userId
        );

        $this->flash('success', 'announcement.rejected_successfully');
        return $this->redirect('/panel/announcements');
    }


    /**
     * @throws Exception
     */
    public function proposeAnnouncement(): ResponseInterface
    {
        $today = new DateTimeImmutable();
        $modified = $today->modify($this->config->announcementDefaultValidDate);

        $dto = ProposeAnnouncementDTO::fromHttpRequest($_POST, $modified);

        $this->proposeAnnouncementUseCase->execute($dto);

        $this->flash('success', 'announcement.proposed_successfully');
        return $this->redirect('/propose');
    }
}