<?php

namespace App\Http\Controller;

use App\Application\UseCase\Announcement\GetAllAnnouncementsUseCase;
use App\Application\UseCase\Countdown\GetAllCountdownsUseCase;
use App\Application\UseCase\Module\GetAllModulesUseCase;
use App\Application\UseCase\User\GetAllUsersUseCase;
use App\Application\UseCase\User\GetUserByIdUseCase;
use App\Domain\Entity\User;
use App\Domain\Enum\AnnouncementStatus;
use App\Domain\Exception\ViewException;
use App\Http\Context\LocaleContext;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Service\CsrfTokenService;
use App\Infrastructure\Translation\LanguageTranslator;
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
        AuthenticationService $authenticationService,
        CsrfTokenService $csrfTokenService,
        LoggerInterface $logger,
        LanguageTranslator $translator,
        LocaleContext $localeContext,
        private readonly GetAllModulesUseCase $getAllModulesUseCase,
        private readonly GetAllUsersUseCase $getAllUsersUseCase,
        private readonly GetUserByIdUseCase $getUserByIdUseCase,
        private readonly GetAllCountdownsUseCase $getAllCountdownsUseCase,
        private readonly GetAllAnnouncementsUseCase $getAllAnnouncementsUseCase,
    ) {
        parent::__construct($authenticationService, $csrfTokenService, $logger, $translator, $localeContext);
    }

    /**
     * Get currently authenticated user
     *
     * @throws ViewException
     * @throws Exception
     */
    private function getActiveUser(): User
    {
        SessionHelper::start();
        $userId = SessionHelper::get('user_id');

        if (!$userId) {
            throw ViewException::userNotAuthenticated();
        }

        return $this->getUserByIdUseCase->execute($userId);
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
        $user = $this->getActiveUser();
        $users = $this->getAllUsersUseCase->execute();

        $this->logger->info("Users page loaded");

        $this->render('pages/users', [
            'user' => $user,
            'users' => $users,
            'footer' => true,
            'navbar' => true
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
        $user = $this->getActiveUser();
        $users = $this->getAllUsersUseCase->execute();
        $countdowns = $this->getAllCountdownsUseCase->execute();

        $usernames = $this->buildUsernamesMap($users);
        $formattedCountdowns = $this->formatCountdowns($countdowns);

        $this->logger->info("Countdowns page loaded");

        $this->render('pages/countdowns', [
            'user' => $user,
            'usernames' => $usernames,
            'countdowns' => $formattedCountdowns,
            'footer' => true,
            'navbar' => true
        ]);
    }

    /**
     * Display modules management page
     *
     * @throws ViewException
     * @throws Exception
     */
    public function modules(): void
    {
        $user = $this->getActiveUser();
        $modules = $this->getAllModulesUseCase->execute();

        $this->logger->info("Modules page loaded");

        $this->render('pages/modules', [
            'user' => $user,
            'modules' => $modules,
            'footer' => true,
            'navbar' => true
        ]);
    }

    /**
     * Display the main admin panel page with overview
     *
     * @throws ViewException
     * @throws Exception
     */
    public function index(): void
    {
        $user = $this->getActiveUser();
        $announcements = $this->getAllAnnouncementsUseCase->execute();
        $users = $this->getAllUsersUseCase->execute();
        $modules = $this->getAllModulesUseCase->execute();

        $this->logger->info("Panel index loaded");

        $this->render('pages/panel', [
            'user' => $user,
            'announcements' => $announcements,
            'users' => $users,
            'modules' => $modules,
            'footer' => true,
            'navbar' => true
        ]);
    }

    /**
     * Display announcements management page
     * Shows pending announcements separately from decided ones
     *
     * @throws ViewException
     * @throws Exception
     */
    public function announcements(): void
    {
        $user = $this->getActiveUser();
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
            'user' => $user,
            'usernames' => $usernames,
            'announcements' => $decidedAnnouncements,
            'pendingAnnouncements' => $pendingAnnouncements,
            'footer' => true,
            'navbar' => true
        ]);
    }
}
