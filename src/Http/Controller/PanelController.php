<?php

namespace App\Http\Controller;

use App\Application\UseCase\Announcement\GetAllAnnouncementsUseCase;
use App\Application\UseCase\Countdown\GetAllCountdownsUseCase;
use App\Application\UseCase\Module\GetAllModulesUseCase;
use App\Application\UseCase\User\GetAllUsersUseCase;
use App\Domain\Enum\AnnouncementStatus;
use App\Domain\Exception\ViewException;
use App\Http\Context\RequestContext;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\View\ViewRendererInterface;
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
        readonly RequestContext $requestContext,
        readonly ViewRendererInterface $renderer,
        readonly FlashMessengerInterface $flash,
        private readonly LoggerInterface $logger,
        private readonly GetAllModulesUseCase $getAllModulesUseCase,
        private readonly GetAllUsersUseCase $getAllUsersUseCase,
        private readonly GetAllCountdownsUseCase $getAllCountdownsUseCase,
        private readonly GetAllAnnouncementsUseCase $getAllAnnouncementsUseCase,
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
                'id' => $announcement->id,
                'title' => $announcement->title,
                'text' => $announcement->text,
                'userId' => $announcement->userId,
                'createdAt' => $announcement->createdAt instanceof DateTimeImmutable
                    ? $announcement->createdAt->format('Y-m-d H:i:s')
                    : $announcement->createdAt,
                'validUntil' => $announcement->validUntil instanceof DateTimeImmutable
                    ? $announcement->validUntil->format('Y-m-d')
                    : $announcement->validUntil,
                'status' => $announcement->status->name,
                'decidedAt' => $announcement->decidedAt instanceof DateTimeImmutable
                    ? $announcement->decidedAt->format('Y-m-d H:i:s')
                    : $announcement->decidedAt,
                'decidedBy' => $announcement->decidedBy,
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
    public function users(): void
    {
        $users = $this->getAllUsersUseCase->execute();

        $this->logger->info("Users page loaded");

        $this->render('pages/users', [
            'users' => $users,
        ]);
    }

    /**
     * Display countdowns management page
     *
     * @throws ViewException
     * @throws Exception
     */
    public function countdowns(): void
    {
        $users = $this->getAllUsersUseCase->execute();
        $countdowns = $this->getAllCountdownsUseCase->execute();

        $usernames = $this->buildUsernamesMap($users);
        $formattedCountdowns = $this->formatCountdowns($countdowns);

        $this->logger->info("Countdowns page loaded");

        $this->render('pages/countdowns', [
            'usernames' => $usernames,
            'countdowns' => $formattedCountdowns,
        ]);
    }

    /**
     * Display modules management page
     *
     * @throws Exception
     */
    public function modules(): void
    {
        $modules = $this->getAllModulesUseCase->execute();

        $this->logger->info("Modules page loaded");

        $this->render('pages/modules', [
            'modules' => $modules,
        ]);
    }

    /**
     * Display the main admin panel page with overview
     *
     * @throws Exception
     */
    public function index(): void
    {
        $announcements = $this->getAllAnnouncementsUseCase->execute();
        $users = $this->getAllUsersUseCase->execute();
        $modules = $this->getAllModulesUseCase->execute();

        $this->logger->info("Panel index loaded");

        $this->render('pages/panel', [
            'announcements' => $announcements,
            'users' => $users,
            'modules' => $modules,
        ]);
    }

    /**
     * Display announcements management page
     * Shows pending announcements separately from decided ones
     *
     * @throws Exception
     */
    public function announcements(): void
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

        $this->render('pages/announcements', [
            'usernames' => $usernames,
            'announcements' => $decidedAnnouncements,
            'pendingAnnouncements' => $pendingAnnouncements,
        ]);
    }
}
