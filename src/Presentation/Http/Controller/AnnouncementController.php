<?php

namespace App\Presentation\Http\Controller;

use App\Application\Announcement\DTO\AddAnnouncementDTO;
use App\Application\Announcement\DTO\EditAnnouncementDTO;
use App\Application\Announcement\DTO\ProposeAnnouncementDTO;
use App\Application\Announcement\UseCase\ApproveRejectAnnouncementUseCase;
use App\Application\Announcement\UseCase\CreateAnnouncementUseCase;
use App\Application\Announcement\UseCase\DeleteAnnouncementUseCase;
use App\Application\Announcement\UseCase\EditAnnouncementUseCase;
use App\Application\Announcement\UseCase\ProposeAnnouncementUseCase;
use App\Domain\Announcement\AnnouncementId;
use App\Domain\Announcement\AnnouncementStatus;
use App\Infrastructure\Configuration\Config;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\Translator;
use App\Presentation\Http\Shared\ViewRendererInterface;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

final class AnnouncementController extends BaseController
{
    public function __construct(
         RequestContext $requestContext,
         ViewRendererInterface $viewRenderer,
        private readonly Translator $translator,
        private readonly Config $config,
        private readonly CreateAnnouncementUseCase $createAnnouncementUseCase,
        private readonly DeleteAnnouncementUseCase $deleteAnnouncementUseCase,
        private readonly EditAnnouncementUseCase $editAnnouncementUseCase,
        private readonly ProposeAnnouncementUseCase $proposeAnnouncementUseCase,
        private readonly ApproveRejectAnnouncementUseCase $approveRejectAnnouncementUseCase,
    ) {
        parent::__construct($requestContext, $viewRenderer);
    }

    public function deleteAnnouncement(): ResponseInterface
    {
        $announcementId = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);

        $this->deleteAnnouncementUseCase->execute($announcementId);

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('announcement.deleted_successfully'),
        ]);
    }

    public function addAnnouncement(): ResponseInterface
    {
        $dto = AddAnnouncementDTO::fromHttpRequest($_POST);
        $userId = $this->getCurrentUserId();

        $this->createAnnouncementUseCase->execute($dto, $userId);

        return $this->jsonResponse(201, [
            'success' => true,
            'message' => $this->translator->translate('announcement.created_successfully'),
        ]);
    }

    public function editAnnouncement(): ResponseInterface
    {
        $id = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
        $dto = EditAnnouncementDTO::fromHttpRequest($_POST);
        $userId = $this->getCurrentUserId();

        $this->editAnnouncementUseCase->execute(
            new AnnouncementId($id),
            $dto,
            $userId
        );

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('announcement.updated_successfully'),
        ]);
    }

    public function approveAnnouncement(): ResponseInterface
    {
        $announcementId = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
        $userId = $this->getCurrentUserId();

        $this->approveRejectAnnouncementUseCase->execute(
            new AnnouncementId($announcementId),
            AnnouncementStatus::APPROVED,
            $userId
        );

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('announcement.approved_successfully'),
        ]);
    }

    public function rejectAnnouncement(): ResponseInterface
    {
        $announcementId = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
        $userId = $this->getCurrentUserId();

        $this->approveRejectAnnouncementUseCase->execute(
            new AnnouncementId($announcementId),
            AnnouncementStatus::REJECTED,
            $userId
        );

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('announcement.rejected_successfully'),
        ]);
    }

    public function proposeAnnouncement(): ResponseInterface
    {
        $today = new DateTimeImmutable();
        $modified = $today->modify($this->config->announcementDefaultValidDate);

        $dto = ProposeAnnouncementDTO::fromHttpRequest($_POST, $modified);

        $this->proposeAnnouncementUseCase->execute($dto);

        return $this->jsonResponse(201, [
            'success' => true,
            'message' => $this->translator->translate('announcement.proposed_successfully'),
        ]);
    }

    protected function jsonResponse(int $statusCode, array $data): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );
    }
}
