<?php

namespace App\Http\Controller;

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
    public function deleteAnnouncement(): void
    {
        $announcementId = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

        if (!$announcementId || $announcementId <= 0) {
            throw AnnouncementException::invalidId();
        }

        $this->deleteAnnouncementUseCase->execute($announcementId);

        SessionHelper::start();
        SessionHelper::set('success', 'announcement.deleted_successfully');
        $this->logger->info("Announcement deleted", ['id' => $announcementId]);

        $this->redirect('/panel/announcements');
    }

    /**
     * @throws AnnouncementException
     * @throws Exception
     */
    public function addAnnouncement(): void
    {
        $title = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
        $text = trim((string)filter_input(INPUT_POST, 'text', FILTER_UNSAFE_RAW));
        $validUntil = (string)filter_input(INPUT_POST, 'valid_until', FILTER_UNSAFE_RAW);

        if (empty($title)) {
            throw AnnouncementException::emptyTitle();
        }

        if (empty($text)) {
            throw AnnouncementException::emptyText();
        }

        $userId = $this->getCurrentUserId();
        $this->createAnnouncementUseCase->execute(
            ['title' => $title, 'text' => $text, 'valid_until' => $validUntil],
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
    public function editAnnouncement(): void
    {
        $id = (int)filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

        if (!$id || $id <= 0) {
            throw AnnouncementException::invalidId();
        }

        $title = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
        $text = trim((string)filter_input(INPUT_POST, 'text', FILTER_UNSAFE_RAW));
        $validUntil = (string)filter_input(INPUT_POST, 'valid_until', FILTER_UNSAFE_RAW);
        $status = (int)filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);

        if (empty($title)) {
            throw AnnouncementException::emptyTitle();
        }

        if (empty($text)) {
            throw AnnouncementException::emptyText();
        }

        $userId = $this->getCurrentUserId();

        $this->editAnnouncementUseCase->execute(
            $id,
            ['title' => $title, 'text' => $text, 'valid_until' => $validUntil, 'status' => $status],
            $userId
        );

        SessionHelper::start();
        SessionHelper::set('success', 'announcement.updated_successfully');
        $this->logger->info("Announcement updated", ['id' => $id]);

        $this->redirect('/panel/announcements');
    }

    /**
     * @throws AnnouncementException
     */
    public function approveAnnouncement(): void
    {
        $announcementId = (int)filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

        if (!$announcementId || $announcementId <= 0) {
            throw AnnouncementException::invalidId();
        }

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
     * @throws AnnouncementException
     */
    public function rejectAnnouncement(): void
    {
        $announcementId = (int)filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

        if (!$announcementId || $announcementId <= 0) {
            throw AnnouncementException::invalidId();
        }

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
     * @throws AnnouncementException
     * @throws Exception
     */
    public function proposeAnnouncement(): void
    {
        $title = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
        $text = trim((string)filter_input(INPUT_POST, 'content', FILTER_UNSAFE_RAW));
        $validUntil = (string)filter_input(INPUT_POST, 'expires_at', FILTER_UNSAFE_RAW);

        if (empty($title)) {
            throw AnnouncementException::emptyTitle();
        }

        if (empty($text)) {
            throw AnnouncementException::emptyText();
        }

        $announcementId = $this->proposeAnnouncementUseCase->execute([
            'title' => $title,
            'text' => $text,
            'valid_until' => $validUntil
        ]);

        SessionHelper::start();
        SessionHelper::set('success', 'announcement.proposed_successfully');
        $this->logger->info("Announcement proposed", ['id' => $announcementId]);

        $this->redirect('/propose');
    }
}