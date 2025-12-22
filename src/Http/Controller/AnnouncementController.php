<?php

namespace App\Http\Controller;

use App\Application\UseCase\Announcement\ApproveRejectAnnouncementUseCase;
use App\Application\UseCase\Announcement\ProposeAnnouncementUseCase;
use App\Domain\Enum\AnnouncementStatus;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\Announcement\CreateAnnouncementUseCase;
use App\Application\UseCase\Announcement\DeleteAnnouncementUseCase;
use App\Application\UseCase\Announcement\EditAnnouncementUseCase;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Security\CsrfService;

class AnnouncementController extends BaseController
{
    public function __construct(
        AuthenticationService $authenticationService,
        CsrfService $csrfService,
        LoggerInterface $logger,
        private readonly CreateAnnouncementUseCase  $createAnnouncementUseCase,
        private readonly DeleteAnnouncementUseCase  $deleteAnnouncementUseCase,
        private readonly EditAnnouncementUseCase    $editAnnouncementUseCase,
        private readonly ProposeAnnouncementUseCase $proposeAnnouncementUseCase,
        private readonly ApproveRejectAnnouncementUseCase $approveRejectAnnouncementUseCase,
    ){
        parent::__construct($authenticationService, $csrfService, $logger);
    }

    public function deleteAnnouncement(): void
    {
        try {
            $announcementId = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
            if (!$announcementId) {
                SessionHelper::set('error', 'Invalid announcement ID.');
                $this->redirect('/panel/announcements');
            }

            $result = $this->deleteAnnouncementUseCase->execute($announcementId);

            if ($result) {
                $this->logger->debug("Announcement deleted", ['id' => $announcementId]);
                SessionHelper::set('success', 'Announcement has been deleted.');
            } else {
                $this->logger->error("Announcement could not be deleted", ['id' => $announcementId]);
                SessionHelper::set('error', 'Failed to delete announcement.');
            }
            $this->redirect('/panel/announcements');
        } catch (Exception) {
            $this->redirect('/panel/announcements');
        }
    }

    public function addAnnouncement(): void
    {
        try{
            $title = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
            $text = trim((string)filter_input(INPUT_POST, 'text', FILTER_UNSAFE_RAW));
            $validUntil = (string)filter_input(INPUT_POST, 'valid_until', FILTER_UNSAFE_RAW);

            $userId = $this->getCurrentUserId();

            $data = ['title' => $title, 'text' => $text, 'valid_until' => $validUntil];

            $success = $this->createAnnouncementUseCase->execute($data, $userId);
            if ($success) {
                SessionHelper::set('success', 'Announcement added.');
                $this->logger->info("Announcement added successfully");
            } else {
                SessionHelper::set('error', 'Error adding announcement.');
                $this->logger->warning("Announcement was not added");
            }
            $this->redirect('/panel/announcements');
        } catch (Exception) {
            $this->redirect('/panel/announcements');
        }
    }

    public function editAnnouncement(): void
    {
        try {
            $id = (int)filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
            $title = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
            $text = trim((string)filter_input(INPUT_POST, 'text', FILTER_UNSAFE_RAW));
            $validUntil = (string)filter_input(INPUT_POST, 'valid_until', FILTER_UNSAFE_RAW);
            $status = (int)filter_input(INPUT_POST, 'status', FILTER_UNSAFE_RAW);

            $userId = $this->getCurrentUserId();

            $data = ['title' => $title, 'text' => $text, 'valid_until' => $validUntil, 'status' => $status];

            $success = $this->editAnnouncementUseCase->execute($id, $data, $userId);

            if ($success) {
                SessionHelper::set('success', 'Announcement updated.');
                $this->logger->info("Announcement updated successfully", ['id' => $id]);
            } else {
                SessionHelper::set('error', 'No changes were made.');
                $this->logger->warning("Announcement update made no changes", ['id' => $id]);
            }
            $this->redirect('/panel/announcements');
        } catch (Exception){
            $this->redirect('/panel/announcements');
        }
    }

    public function approveAnnouncement(): void
    {
        try {
            $userId = $this->getCurrentUserId();
            $announcementId = (int)filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

            if ($announcementId <= 0) {
                throw new InvalidArgumentException('announcement.invalid_id');
            }

            $this->approveRejectAnnouncementUseCase->execute(
                $announcementId,
                AnnouncementStatus::APPROVED,
                $userId
            );

            SessionHelper::set('success', 'announcement.approved_successfully');
            $this->logger->info("Announcement approved", ['id' => $announcementId]);

        } catch (InvalidArgumentException $e) {
            SessionHelper::set('error', $e->getMessage());
            $this->logger->warning("Announcement approval failed", ['error' => $e->getMessage()]);

        } catch (Exception $e) {
            SessionHelper::set('error', 'announcement.unexpected_error');
            $this->logger->error("Unexpected error in approveAnnouncement", ['error' => $e->getMessage()]);
        }

        $this->redirect('/panel/announcements');
    }

    public function rejectAnnouncement(): void
    {
        try {
            $userId = $this->getCurrentUserId();
            $announcementId = (int)filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

            if ($announcementId <= 0) {
                throw new InvalidArgumentException('announcement.invalid_id');
            }

            $this->approveRejectAnnouncementUseCase->execute(
                $announcementId,
                AnnouncementStatus::REJECTED,
                $userId
            );

            SessionHelper::set('success', 'announcement.rejected_successfully');
            $this->logger->info("Announcement rejected", ['id' => $announcementId]);

        } catch (InvalidArgumentException $e) {
            SessionHelper::set('error', $e->getMessage());
            $this->logger->warning("Announcement rejection failed", ['error' => $e->getMessage()]);

        } catch (Exception $e) {
            SessionHelper::set('error', 'announcement.unexpected_error');
            $this->logger->error("Unexpected error in rejectAnnouncement", ['error' => $e->getMessage()]);
        }

        $this->redirect('/panel/announcements');
    }


    public function proposeAnnouncement(): void
    {
        try {
            $title = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
            $text = trim((string)filter_input(INPUT_POST, 'content', FILTER_UNSAFE_RAW));
            $validUntil = (string)filter_input(INPUT_POST, 'expires_at', FILTER_UNSAFE_RAW);

            $data = [
                'title' => $title,
                'text' => $text,
                'valid_until' => $validUntil
            ];

            $announcementId = $this->proposeAnnouncementUseCase->execute($data);

            SessionHelper::set('success', 'Ogłoszenie zostało zgłoszone! Czeka na akceptację administratora.');
            $this->logger->info("Announcement proposed successfully", ['id' => $announcementId]);

        } catch (InvalidArgumentException $e) {
            SessionHelper::set('error', $e->getMessage());
            $this->logger->warning("Announcement validation failed", ['error' => $e->getMessage()]);

        } catch (Exception $e) {
            SessionHelper::set('error', 'Coś poszło nie tak. Spróbuj ponownie.');
            $this->logger->error("Unexpected error in proposeAnnouncement", ['error' => $e->getMessage()]);
        }

        $this->redirect('/propose');
    }
}