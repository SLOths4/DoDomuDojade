<?php

namespace App\Http\Controller;

use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\Announcement\GetAllAnnouncementsUseCase;
use App\Application\UseCase\Countdown\GetAllCountdownsUseCase;
use App\Application\UseCase\Countdown\GetCountdownByIdUseCase;
use App\Application\UseCase\Module\GetAllModulesUseCase;
use App\Application\UseCase\User\GetAllUsersUseCase;
use App\Application\UseCase\User\GetUserByIdUseCase;
use App\Application\UseCase\User\GetUserByUsernameUseCase;
use App\Domain\User;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Security\CsrfService;

/**
 * Panel controller
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 */
class PanelController extends BaseController
{
    function __construct(
        AuthenticationService        $authenticationService,
        CsrfService                  $csrfService,
        LoggerInterface              $logger,
        private readonly ErrorController     $errorController,
        private readonly GetAllModulesUseCase $getAllModulesUseCase,
        private readonly GetAllUsersUseCase  $getAllUsersUseCase,
        private readonly GetUserByIdUseCase  $getUserByIdUseCase,
        private readonly GetUserByUsernameUseCase $getUserByUsernameUseCase,
        private readonly GetAllCountdownsUseCase $getAllCountdownsUseCase,
        private readonly GetAllAnnouncementsUseCase $getAllAnnouncementsUseCase,
    )
    {
        parent::__construct($authenticationService, $csrfService, $logger);
    }

    private function getActiveUser(): User
    {
        try {
            $userId = SessionHelper::get('user_id');
            return $this->getUserByIdUseCase->execute($userId);
        } catch (Exception $e) {
            $this->logger->error("Error while getting user: " . $e->getMessage());
            $this->errorController->internalServerError();
            exit;
        }
    }

    private function buildUsernamesMap(array $users): array
    {
        $usernames = [];
        foreach ($users as $u) {
            $usernames[$u->id] = $u->username;
        }
        return $usernames;
    }

    private function formatCountdowns(array $countdowns): array
    {
        $out = [];
        foreach ($countdowns as $countdown) {
            $out[] = (object) [
                'id' => $countdown->id,
                'title' => $countdown->title,
                'userId' => $countdown->userId,
                'countTo' => $countdown->countTo instanceof DateTimeImmutable
                    ? $countdown->countTo->format('Y-m-d')
                    : $countdown->countTo,
            ];
        }
        return $out;
    }

    private function formatAnnouncements(array $announcements): array
    {
        $out = [];
        foreach ($announcements as $announcement) {
            $out[] = (object)[
                'id' => $announcement->id,
                'title' => $announcement->title,
                'text' => $announcement->text,
                'userId' => $announcement->userId,
                'date' => $announcement->date instanceof DateTimeImmutable
                    ? $announcement->date->format('Y-m-d')
                    : $announcement->date,
                'validUntil' => $announcement->validUntil instanceof DateTimeImmutable
                    ? $announcement->validUntil->format('Y-m-d')
                    : $announcement->validUntil
            ];
        }
        return $out;
    }

    public function users(): void
    {
        try {
            $user = $this->getActiveUser();
            $users = $this->getAllUsersUseCase->execute();

            $this->render('pages/users', [
                'user' => $user,
                'users' => $users,
                'footer' => true,
                'navbar' => true
            ]);
        } catch (Exception $e) {
            $this->handleError("Failed to load users page", "Failed to load users page: " . $e->getMessage());
        }
    }

    public function countdowns(): void
    {
        try {
            $user = $this->getActiveUser();
            $users = $this->getAllUsersUseCase->execute();
            $countdowns = $this->getAllCountdownsUseCase->execute();

            $usernames = $this->buildUsernamesMap($users);
            $formattedCountdowns = $this->formatCountdowns($countdowns);

            $this->render('pages/countdowns', [
                'user' => $user,
                'usernames' => $usernames,
                'countdowns' => $formattedCountdowns,
                'footer' => true,
                'navbar' => true
            ]);
        } catch (Exception $e) {
            $this->handleError("Failed to load countdowns page", "Failed to load countdown page: " . $e->getMessage());
        }
    }

    public function modules(): void
    {
        try {
            $user = $this->getActiveUser();
            $modules = $this->getAllModulesUseCase->execute();
            $this->render('pages/modules', [
                'user' => $user,
                'modules' => $modules,
                'footer' => true,
                'navbar' => true
            ]);
        } catch (Exception $e) {
            $this->handleError("Failed to load modules page", "Modules error: ".$e->getMessage(), "/panel/modules");
        }
    }

    public function login(): void
    {
        try {
            $this->setCsrf();
            $this->render('pages/login', [
                'footer' => true
            ]);
        } catch (Exception $e) {
            $this->handleError("Failed to load login page", "Login error: ".$e->getMessage());
        }
    }

    public function authenticate(): void
    {
        try {
                $this->logger->debug("User verification request received.");

                $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
                $password = trim((string)filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW));

                if ($password === '' || $username === '') {
                    $this->logger->error("Username or password is empty");
                    SessionHelper::set("error", "Username and password are required.");
                    $this->redirect("/login");
                }

                $user = $this->getUserByUsernameUseCase->execute($username);

                if ($user && password_verify($password, $user->passwordHash)) {
                    $this->logger->debug("Correct password for given username.");
                    $this->setCsrf();
                    SessionHelper::set('user_id', $user->id);
                    $this->redirect("/panel");
                } else {
                    $this->logger->debug("Incorrect credentials");
                    SessionHelper::set('error', 'Invalid username or password.');
                    $this->redirect("/login");
                }
        } catch (Exception $e) {
            $this->handleError("Authentication failed", "Authentication error: " . $e->getMessage());
        }
    }

    public function index(): void
    {
        try {
            $user = $this->getActiveUser();

            $announcements = $this->getAllAnnouncementsUseCase->execute();
            $users = $this->getAllUsersUseCase->execute();
            $modules = $this->getAllModulesUseCase->execute();

            $this->render('pages/panel', [
                'user' => $user,
                'announcements' => $announcements,
                'users' => $users,
                'modules' => $modules,
                'footer' => true,
                'navbar' => true
            ]);
        } catch (Exception $e) {
            $this->handleError("Failed to load panel", "Failed to load index: " . $e->getMessage());
        }
    }

    public function announcements(): void
    {
        try {
            $user = $this->getActiveUser();
            $users = $this->getAllUsersUseCase->execute();
            $announcements = $this->getAllAnnouncementsUseCase->execute();

            $usernames = $this->buildUsernamesMap($users);
            $formattedAnnouncements = $this->formatAnnouncements($announcements);

            $this->render('pages/announcements', [
                'user' => $user,
                'usernames' => $usernames,
                'announcements' => $formattedAnnouncements,
                'footer' => true,
                'navbar' => true
            ]);
        } catch (Exception) {
            $this->redirect('/panel');
        }
    }
}
