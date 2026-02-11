<?php

namespace App\Presentation\Http\Controller;

use App\Application\Announcement\DTO\AddAnnouncementDTO;
use App\Application\Announcement\DTO\EditAnnouncementDTO;
use App\Application\Announcement\DTO\ProposeAnnouncementDTO;
use App\Application\Announcement\UseCase\ApproveRejectAnnouncementUseCase;
use App\Application\Announcement\UseCase\CreateAnnouncementUseCase;
use App\Application\Announcement\UseCase\DeleteAnnouncementUseCase;
use App\Application\Announcement\UseCase\EditAnnouncementUseCase;
use App\Application\Announcement\UseCase\GetAllAnnouncementsUseCase;
use App\Application\Announcement\UseCase\GetAnnouncementByIdUseCase;
use App\Application\Announcement\UseCase\ProposeAnnouncementUseCase;
use App\Domain\Announcement\AnnouncementException;
use App\Domain\Announcement\AnnouncementId;
use App\Domain\Announcement\AnnouncementStatus;
use App\Domain\Shared\InvalidDateTimeException;
use App\Domain\Shared\MissingParameterException;
use App\Domain\User\UserException;
use App\Infrastructure\Configuration\Config;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Presenter\AnnouncementPresenter;
use App\Presentation\Http\Shared\Translator;
use App\Presentation\Http\Shared\ViewRendererInterface;
use DateMalformedStringException;
use DateTimeImmutable;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AnnouncementController extends BaseController
{

    public function __construct(
        RequestContext                                    $requestContext,
        ViewRendererInterface                             $viewRenderer,
        private readonly ServerRequestInterface           $request,
        private readonly Translator                       $translator,
        private readonly Config                           $config,
        private readonly CreateAnnouncementUseCase        $createAnnouncementUseCase,
        private readonly DeleteAnnouncementUseCase        $deleteAnnouncementUseCase,
        private readonly EditAnnouncementUseCase          $editAnnouncementUseCase,
        private readonly ProposeAnnouncementUseCase       $proposeAnnouncementUseCase,
        private readonly ApproveRejectAnnouncementUseCase $approveRejectAnnouncementUseCase,
        private readonly GetAnnouncementByIdUseCase       $getAnnouncementByIdUseCase,
        private readonly GetAllAnnouncementsUseCase       $getAllAnnouncementsUseCase,
        private readonly AnnouncementPresenter            $presenter,
    ) {
        parent::__construct($requestContext, $viewRenderer);
    }

    /**
     * @throws Exception
     */
    public function delete(array $vars = []): ResponseInterface
    {
        $id = (string)$vars['id'];

        $this->deleteAnnouncementUseCase->execute(new AnnouncementId($id));

        return $this->noContentResponse();
    }

    /**
     * @throws Exception
     */
    public function get(array $vars = []): ResponseInterface
    {
        $id = (string)$vars['id'];

        $announcement = $this->getAnnouncementByIdUseCase->execute(new AnnouncementId($id));

        return $this->jsonResponse(200, $announcement->toArray());
    }

    /**
     * @throws Exception
     */
    public function getAll(): ResponseInterface
    {
        $announcements = $this->getAllAnnouncementsUseCase->execute();

        return $this->jsonResponse(200, $this->presenter->toApi($announcements));
    }

    /**
     * @throws UserException
     * @throws InvalidDateTimeException
     * @throws MissingParameterException
     * @throws Exception
     */
    public function add(): ResponseInterface
    {
        $body = json_decode((string)$this->request->getBody(), true);

        $dto = AddAnnouncementDTO::fromArray($body);
        $userId = $this->getCurrentUserId();

        $this->createAnnouncementUseCase->execute($dto, $userId);

        return $this->jsonResponse(201, [
            'success' => true,
            'message' => $this->translator->translate('announcement.created_successfully'),
        ]);
    }

    /**
     * @throws UserException
     * @throws DateMalformedStringException
     * @throws AnnouncementException
     */
    public function update(array $vars = []): ResponseInterface
    {
        $id = (string)$vars['id'];
        $body = json_decode((string)$this->request->getBody(), true);

        $dto = EditAnnouncementDTO::fromArray($body);
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

    /**
     * @throws UserException
     * @throws Exception
     */
    public function approve(array $vars = []): ResponseInterface
    {
        $id = (string)$vars['id'];
        $userId = $this->getCurrentUserId();

        $this->approveRejectAnnouncementUseCase->execute(
            new AnnouncementId($id),
            AnnouncementStatus::APPROVED,
            $userId
        );

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('announcement.approved_successfully'),
        ]);
    }

    /**
     * @throws UserException
     * @throws Exception
     */
    public function reject(array $vars = []): ResponseInterface
    {
        $id = (string)$vars['id'];
        $userId = $this->getCurrentUserId();

        $this->approveRejectAnnouncementUseCase->execute(
            new AnnouncementId($id),
            AnnouncementStatus::REJECTED,
            $userId
        );

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('announcement.rejected_successfully'),
        ]);
    }

    /**
     * @param array $vars
     * @return ResponseInterface
     * @throws AnnouncementException
     * @throws DateMalformedStringException
     * @throws InvalidDateTimeException
     * @throws MissingParameterException
     */
    public function propose(array $vars = []): ResponseInterface
    {
        $body = json_decode((string)$this->request->getBody(), true);

        $today = new DateTimeImmutable();
        $modified = $today->modify($this->config->announcementDefaultValidDate);

        $dto = ProposeAnnouncementDTO::fromArray($body, $modified);

        $this->proposeAnnouncementUseCase->execute($dto);

        return $this->jsonResponse(201, [
            'success' => true,
            'message' => $this->translator->translate('announcement.proposed_successfully'),
        ]);
    }
}
