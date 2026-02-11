<?php

namespace App\Presentation\Http\Controller;

use App\Application\Announcement\UseCase\GetAllAnnouncementsUseCase;
use App\Application\Countdown\UseCase\GetAllCountdownsUseCase;
use App\Application\Module\UseCase\GetAllModulesUseCase;
use App\Application\User\UseCase\GetAllUsersUseCase;
use App\Domain\Announcement\AnnouncementStatus;
use App\Domain\Module\Module;
use App\Domain\Shared\ViewException;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\DTO\ModuleViewDTO;
use App\Presentation\Http\Presenter\AnnouncementPresenter;
use App\Presentation\Http\Shared\FlashMessengerInterface;
use App\Presentation\Http\Shared\Translator;
use App\Presentation\Http\Shared\ViewRendererInterface;
use App\Presentation\View\TemplateNames;
use DateTimeImmutable;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Panel controller - handles admin panel views and data aggregation
 *
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 */
class PanelController extends BaseController
{
    public function __construct(
        RequestContext                              $requestContext,
        ViewRendererInterface                       $renderer,
        readonly FlashMessengerInterface            $flash,
        private readonly LoggerInterface            $logger,
        private readonly GetAllModulesUseCase       $getAllModulesUseCase,
        private readonly GetAllUsersUseCase         $getAllUsersUseCase,
        private readonly GetAllCountdownsUseCase    $getAllCountdownsUseCase,
        private readonly GetAllAnnouncementsUseCase $getAllAnnouncementsUseCase,
        private readonly Translator                 $translator,
        private readonly AnnouncementPresenter      $announcementPresenter
    ){
        parent::__construct($requestContext, $renderer);
    }

    /**
     * Build map of user IDs to usernames for display purposes
     */
    private function buildUsernamesMap(array $users): array
    {
        $usernames = [];
        foreach ($users as $user) {
            $usernames[$user->id] = $user->username;
        }
        return $usernames;
    }

    /**
     * Format countdown objects for display
     * Ensures consistent date formatting
     */
    private function formatCountdowns(array $countdowns): array
    {
        $formatted = [];
        foreach ($countdowns as $countdown) {
            $formatted[] = (object)[
                'id' => $countdown->id,
                'title' => $countdown->title,
                'userId' => $countdown->userId,
                'countTo' => $countdown->countTo instanceof DateTimeImmutable
                    ? $countdown->countTo->format('Y-m-d')
                    : $countdown->countTo,
            ];
        }
        return $formatted;
    }

    /**
     * Display users management page
     *
     * @throws ViewException
     * @throws Exception
     */
    public function users(): ResponseInterface
    {
        $users = $this->getAllUsersUseCase->execute();

        $this->logger->info("Users page loaded");

        return $this->render(TemplateNames::USERS->value, [
            'users' => $users,
        ]);
    }

    /**
     * Display countdowns management page
     *
     * @throws ViewException
     * @throws Exception
     */
    public function countdowns(): ResponseInterface
    {
        $users = $this->getAllUsersUseCase->execute();
        $countdowns = $this->getAllCountdownsUseCase->execute();

        $usernames = $this->buildUsernamesMap($users);
        $formattedCountdowns = $this->formatCountdowns($countdowns);

        $this->logger->info("Countdowns page loaded");

        return $this->render(TemplateNames::COUNTDOWNS->value, [
            'usernames' => $usernames,
            'countdowns' => $formattedCountdowns,
        ]);
    }

    /**
     * Display modules management page
     *
     * @throws Exception
     */
    public function modules(): ResponseInterface
    {
        $modules = $this->getAllModulesUseCase->execute();

        $translatedModules = array_map(
            fn(Module $module) => new ModuleViewDTO(
                id: $module->id,
                moduleName: $module->moduleName->value,
                moduleNameLabel: $this->translator->translate('module_name.' . $module->moduleName->value),
                isActive: $module->isActive,
                startTime: $module->startTime,
                endTime: $module->endTime,
            ),
            $modules
        );

        $this->logger->info("Modules page loaded");

         return $this->render(TemplateNames::MODULES->value, [
            'modules' => $translatedModules,
        ]);
    }

    /**
     * Display the main admin panel page with overview
     *
     * @throws Exception
     */
    public function index(): ResponseInterface
    {
        $this->logger->info("Panel index loaded");
        return $this->render(TemplateNames::PANEL->value);
    }

    /**
     * Display announcement management page
     * Shows pending announcements separately from decided ones
     *
     * @throws Exception
     */
    public function announcements(): ResponseInterface
    {
        $users = $this->getAllUsersUseCase->execute();
        $announcements = $this->getAllAnnouncementsUseCase->execute();

        $usernames = $this->buildUsernamesMap($users);

        $announcementDTOs = $this->announcementPresenter->toView($announcements, $usernames);

        $pendingAnnouncements = array_filter(
            $announcementDTOs,
            fn($a) => $a->status === AnnouncementStatus::PENDING->value,
        );
        $decidedAnnouncements = array_filter(
            $announcementDTOs,
            fn($a) => $a->status !== AnnouncementStatus::PENDING->value,
        );

        $this->logger->debug("Announcements page loaded");

        return $this->render(TemplateNames::ANNOUNCEMENTS->value, [
            'usernames' => $usernames,
            'announcements' => $decidedAnnouncements,
            'pendingAnnouncements' => $pendingAnnouncements,
        ]);
    }
}
