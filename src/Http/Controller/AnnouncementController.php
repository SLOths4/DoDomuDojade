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
use App\Domain\Enum\AnnouncementStatus;
use App\Domain\Exception\AnnouncementException;
use App\Http\Context\LocaleContext;
use App\Infrastructure\Service\CsrfTokenService;
use App\Infrastructure\Translation\LanguageTranslator;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Security\AuthenticationService;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;


class AnnouncementController extends BaseController
{
    public function __construct(
        AuthenticationService                               $authenticationService,
        CsrfTokenService                                    $csrfTokenService,
        LoggerInterface                                     $logger,
        LanguageTranslator                                  $translator,
        LocaleContext                                       $localeContext,
        private readonly CreateAnnouncementUseCase          $createAnnouncementUseCase,
        private readonly DeleteAnnouncementUseCase          $deleteAnnouncementUseCase,
        private readonly EditAnnouncementUseCase            $editAnnouncementUseCase,
        private readonly ProposeAnnouncementUseCase         $proposeAnnouncementUseCase,
        private readonly ApproveRejectAnnouncementUseCase   $approveRejectAnnouncementUseCase,
    ){
        parent::__construct($authenticationService, $csrfTokenService, $logger, $translator, $localeContext);
    }

    /**
     * @throws AnnouncementException
     * @throws Exception
     */
    #[NoReturn]
    public function deleteAnnouncement(): void
    {
        $announcementId = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

        $this->deleteAnnouncementUseCase->execute($announcementId);

        SessionHelper::start();
        SessionHelper::set('success', 'announcement.deleted_successfully');
        $this->logger->info("Announcement deleted", ['id' => $announcementId]);

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

        SessionHelper::start();
        SessionHelper::set('success', 'announcement.created_successfully');
        $this->logger->info("Announcement created successfully");

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

        SessionHelper::start();
        SessionHelper::set('success', 'announcement.updated_successfully');
        $this->logger->info("Announcement updated", ['id' => $id]);

        $this->redirect('/panel/announcements');
    }

    /**
     * @throws Exception
     */
    public function approveAnnouncement(): void
    {
        $announcementId = (int)filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

        $userId = $this->getCurrentUserId();

        $this->approveRejectAnnouncementUseCase->execute(
            $announcementId,
            AnnouncementStatus::APPROVED,
            $userId
        );

        SessionHelper::start();
        SessionHelper::set('success', 'announcement.approved_successfully');
        $this->logger->info("Announcement approved", ['id' => $announcementId]);

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

        SessionHelper::start();
        SessionHelper::set('success', 'announcement.rejected_successfully');
        $this->logger->info("Announcement rejected", ['id' => $announcementId]);

        $this->redirect('/panel/announcements');
    }


    /**
     * @throws Exception
     */
    #[NoReturn]
    public function proposeAnnouncement(): void
    {
        $dto = ProposeAnnouncementDTO::fromHttpRequest($_POST);

        $announcementId = $this->proposeAnnouncementUseCase->execute($dto);

        SessionHelper::start();
        SessionHelper::set('success', 'announcement.proposed_successfully');
        $this->logger->info("Announcement proposed", ['id' => $announcementId]);

        $this->redirect('/propose');
    }
}