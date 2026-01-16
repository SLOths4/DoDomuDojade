<?php

namespace App\Http\Controller;

use App\Application\Announcement\GetAllAnnouncementsUseCase;
use App\Application\Countdown\GetAllCountdownsUseCase;
use App\Application\Module\GetAllModulesUseCase;
use App\Application\User\GetAllUsersUseCase;
use App\Domain\Announcement\AnnouncementStatus;
use App\Domain\Exception\ViewException;
use App\Domain\Module\Module;
use App\Http\Context\RequestContext;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\Translation\Translator;
use App\Infrastructure\View\ViewRendererInterface;
use App\Presentation\DataTransferObject\ModuleViewDTO;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Panel controller - handles admin panel views and data aggregation
 *
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 */
class PanelController extends BaseController
{
    public function __construct(
        readonly RequestContext                     $requestContext,
        readonly ViewRendererInterface              $renderer,
        readonly FlashMessengerInterface            $flash,
        private readonly LoggerInterface            $logger,
        private readonly GetAllModulesUseCase       $getAllModulesUseCase,
        private readonly GetAllUsersUseCase         $getAllUsersUseCase,
        private readonly GetAllCountdownsUseCase    $getAllCountdownsUseCase,
        private readonly GetAllAnnouncementsUseCase $getAllAnnouncementsUseCase,
        private readonly Translator                 $translator,
    ){}

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
     * Format announcement objects for display
     * Separates pending and decided announcements, ensures consistent formatting
     */
    private function formatAnnouncements(array $announcements): array
    {
        $formatted = [];
        foreach ($announcements as $announcement) {
            $formatted[] = (object)[
                'id' => $announcement->getId(),
                'title' => $announcement->getTitle(),
                'text' => $announcement->getText(),
                'userId' => $announcement->getUserId(),
                'createdAt' => $announcement->getCreatedAt() instanceof DateTimeImmutable
                    ? $announcement->getCreatedAt()->format('Y-m-d H:i:s')
                    : $announcement->getCreatedAt(),
                'validUntil' => $announcement->getValidUntil() instanceof DateTimeImmutable
                    ? $announcement->getValidUntil()->format('Y-m-d')
                    : $announcement->getValidUntil(),
                'status' => $announcement->getStatus()->name,
                'decidedAt' => $announcement->getDecidedAt() instanceof DateTimeImmutable
                    ? $announcement->getDecidedAt()->format('Y-m-d H:i:s')
                    : $announcement->getDecidedAt(),
                'decidedBy' => $announcement->getDecidedBy(),
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
    public function users(): string
    {
        $users = $this->getAllUsersUseCase->execute();

        $this->logger->info("Users page loaded");

        return $this->render('pages/users', [
            'users' => $users,
        ]);
    }

    /**
     * Display countdowns management page
     *
     * @throws ViewException
     * @throws Exception
     */
    public function countdowns(): string
    {
        $users = $this->getAllUsersUseCase->execute();
        $countdowns = $this->getAllCountdownsUseCase->execute();

        $usernames = $this->buildUsernamesMap($users);
        $formattedCountdowns = $this->formatCountdowns($countdowns);

        $this->logger->info("Countdowns page loaded");

        return $this->render('pages/countdowns', [
            'usernames' => $usernames,
            'countdowns' => $formattedCountdowns,
        ]);
    }

    /**
     * Display modules management page
     *
     * @throws Exception
     */
    public function modules(): string
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

        return $this->render('pages/modules', [
            'modules' => $translatedModules,
        ]);
    }

    /**
     * Display the main admin panel page with overview
     *
     * @throws Exception
     */
    public function index(): string
    {
        $announcements = $this->getAllAnnouncementsUseCase->execute();
        $users = $this->getAllUsersUseCase->execute();
        $modules = $this->getAllModulesUseCase->execute();

        $this->logger->info("Panel index loaded");

        return $this->render('pages/panel');
    }

    /**
     * Display announcements management page
     * Shows pending announcements separately from decided ones
     *
     * @throws Exception
     */
    public function announcements(): string
    {
        $users = $this->getAllUsersUseCase->execute();
        $announcements = $this->getAllAnnouncementsUseCase->execute();

        $usernames = $this->buildUsernamesMap($users);
        $allAnnouncements = $this->formatAnnouncements($announcements);

        $pendingAnnouncements = array_filter(
            $allAnnouncements,
            fn($a) => $a->status === AnnouncementStatus::PENDING->name
        );
        $decidedAnnouncements = array_filter(
            $allAnnouncements,
            fn($a) => $a->status !== AnnouncementStatus::PENDING->name
        );

        $this->logger->info("Announcements page loaded");

        return $this->render('pages/announcements', [
            'usernames' => $usernames,
            'announcements' => $decidedAnnouncements,
            'pendingAnnouncements' => $pendingAnnouncements,
        ]);
    }

    public function test(): string {
        return $this->render('pages/TEST');
    }
}
