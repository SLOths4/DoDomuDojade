<?php

namespace App\Http\Controller;

use Exception;
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
        private readonly CreateAnnouncementUseCase $createAnnouncementUseCase,
        private readonly DeleteAnnouncementUseCase $deleteAnnouncementUseCase,
        private readonly EditAnnouncementUseCase   $editAnnouncementUseCase,
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

            $data = ['title' => $title, 'text' => $text, 'valid_until' => $validUntil];

            $success = $this->editAnnouncementUseCase->execute($id, $data);

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
}