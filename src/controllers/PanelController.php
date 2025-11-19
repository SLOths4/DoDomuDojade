<?php

namespace src\controllers;

use DateTime;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use src\core\Controller;
use src\entities\User;
use src\infrastructure\helpers\SessionHelper;
use src\service\AnnouncementService;
use src\service\CountdownService;
use src\service\ModuleService;
use src\service\UserService;
use DateTimeImmutable;
use Throwable;

/**
 * Panel controller
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 */
class PanelController extends Controller
{
    function __construct(
        private readonly ErrorController     $errorController,
        private readonly LoggerInterface     $logger,
        private readonly ModuleService       $moduleService,
        private readonly AnnouncementService $announcementService,
        private readonly UserService         $userService,
        private readonly CountdownService    $countdownService,
    )
    {
        SessionHelper::start();
    }

    /**
     * @throws RandomException
     */
    private function setCsrf(): void
    {
        if (!SessionHelper::has('csrf_token')) {
            SessionHelper::set('csrf_token', bin2hex(random_bytes(32)));
        }
    }

    private function checkCsrf(): void
    {
        try {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== SessionHelper::get('csrf_token')) {
                $this->logger->error("Invalid CSRF token.");
                SessionHelper::set('error', 'Invalid CSRF token.');
                $this->redirect('/login');
            }
        } catch (Exception $e) {
            $this->logger->error("CSRF error occurred: ". $e->getMessage());
            $this->errorController->internalServerError();
        }
    }

    private function checkIsUserLoggedIn(): void
    {
        $userId = SessionHelper::get('user_id');
        if (!$userId) {
            $this->handleError("Please sign in to continue", "No user logged in");
        }
    }

    private function getActiveUser(): User
    {
        try {
            $this->checkIsUserLoggedIn();
            $userId = SessionHelper::get('user_id');
            return $this->userService->getById($userId);
        } catch (Exception $e) {
            $this->logger->error("Error while getting user: " . $e->getMessage());
            $this->errorController->internalServerError();
            exit;
        }
    }

    #[NoReturn]
    private function handleError(string $visibleMessage, string $logMessage, string $redirectTo = '/login'): void
    {
        $this->logger->error($logMessage);
        SessionHelper::set('error', $visibleMessage);
        $this->redirect($redirectTo);
    }

    #[NoReturn]
    private function redirect(string $to): void
    {
        header("Location: $to");
        exit;
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
            $out[] = (object) [
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

    public function index(): void
    {
        try {
            $user = $this->getActiveUser();

            $announcements = $this->announcementService->getAll();
            $users = $this->userService->getAll();
            $modules = $this->moduleService->getAll();

            $this->render('panel', [
                'user' => $user,
                'announcements' => $announcements,
                'users' => $users,
                'modules' => $modules
            ]);
        } catch (Exception $e) {
            $this->handleError("Failed to load panel", "Failed to load index: " . $e->getMessage());
        }
    }

    public function users(): void
    {
        try {
            $user = $this->getActiveUser();
            $users = $this->userService->getAll();

            $this->render('users', [
                'user' => $user,
                'users' => $users
            ]);
        } catch (Exception $e) {
            $this->handleError("Failed to load users page", "Failed to load users page: " . $e->getMessage());
        }
    }

    public function countdowns(): void
    {
        try {
            $user = $this->getActiveUser();
            $users = $this->userService->getAll();
            $countdowns = $this->countdownService->getAll();

            $usernames = $this->buildUsernamesMap($users);
            $formattedCountdowns = $this->formatCountdowns($countdowns);

            $this->render('countdowns', [
                'user' => $user,
                'usernames' => $usernames,
                'countdowns' => $formattedCountdowns
            ]);
        } catch (Exception $e) {
            $this->handleError("Failed to load countdowns page", "Failed to load countdown page: " . $e->getMessage());
        }
    }

    public function announcements(): void
    {
        try {
            $user = $this->getActiveUser();
            $users = $this->userService->getAll();
            $announcements = $this->announcementService->getAll();

            $usernames = $this->buildUsernamesMap($users);
            $formattedAnnouncements = $this->formatAnnouncements($announcements);

            $this->render('announcements', [
                'user' => $user,
                'usernames' => $usernames,
                'announcements' => $formattedAnnouncements
            ]);
        } catch (Exception $e) {
            $this->handleError("Failed to load announcements page", "Announcements error: ".$e->getMessage(), "/panel/announcements");
        }
    }

    public function modules(): void
    {
        try {
            $user = $this->getActiveUser();
            $modules = $this->moduleService->getAll();
            $this->render('modules', [
                'user' => $user,
                'modules' => $modules
            ]);
        } catch (Exception $e) {
            $this->handleError("Failed to load modules page", "Modules error: ".$e->getMessage(), "/panel/modules");
        }
    }

    #[NoReturn]
    public function logout(): void
    {
        SessionHelper::destroy();
        $this->redirect('/login');
    }

    public function login(): void
    {
        try {
            $this->setCsrf();
            $this->render('login');
        } catch (Exception $e) {
            $this->handleError("Failed to load login page", "Login error: ".$e->getMessage());
        }
    }

    public function authenticate(): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            try {
                $this->logger->debug("User verification request received.");
                $this->checkCsrf();

                $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
                $password = trim((string)filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW));

                if ($password === '' || $username === '') {
                    $this->logger->error("Username or password is empty");
                    SessionHelper::set("error", "Username and password are required.");
                    $this->redirect("/login");
                }

                $user = $this->userService->getByExactUsername($username);

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
                $this->handleError("Authentication failed", "Authentication error: ".$e->getMessage());
            }
        }
    }

    public function deleteAnnouncement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $announcementId = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
            if (!$announcementId) {
                $this->logger->error("Invalid announcement ID");
                SessionHelper::set('error', 'Invalid announcement ID.');
                $this->redirect('/panel/announcements');
            }

            try {
                $this->logger->debug("Announcement deletion request received", ['id' => $announcementId]);

                $result = $this->announcementService->delete($announcementId);

                if ($result) {
                    $this->logger->debug("Announcement deleted", ['id' => $announcementId]);
                    SessionHelper::set('success', 'Announcement has been deleted.');
                } else {
                    $this->logger->error("Announcement could not be deleted", ['id' => $announcementId]);
                    SessionHelper::set('error', 'Failed to delete announcement.');
                }
                $this->redirect('/panel/announcements');
            } catch (Exception $e) {
                $this->logger->error('Announcement deletion failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Error occurred while deleting announcement.');
                $this->redirect('/panel/announcements');
            }
        }
    }

    public function addAnnouncement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $title = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
            $text = trim((string)filter_input(INPUT_POST, 'text', FILTER_UNSAFE_RAW));
            $validUntil = (string)filter_input(INPUT_POST, 'valid_until', FILTER_UNSAFE_RAW);

            $userId = SessionHelper::get('user_id');

            try {
                $data = [
                    'title' => $title,
                    'text' => $text,
                    'valid_until' => $validUntil
                ];

                $success = $this->announcementService->create($data, $userId);

                if ($success) {
                    SessionHelper::set('success', 'Announcement created.');
                    $this->logger->info("Announcement added successfully", ['user_id' => $userId]);
                } else {
                    SessionHelper::set('error', 'Failed to add announcement.');
                    $this->logger->warning("Announcement add failed", ['user_id' => $userId]);
                }

            } catch (Exception $e) {
                SessionHelper::set('error', 'Error while adding announcement.');
                $this->logger->error("Exception while adding announcement", ['error' => $e->getMessage()]);
            }

            $this->redirect('/panel/announcements');
        }
    }

    public function editAnnouncement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $id = (int)filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
            $title = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
            $text = trim((string)filter_input(INPUT_POST, 'text', FILTER_UNSAFE_RAW));
            $validUntil = (string)filter_input(INPUT_POST, 'valid_until', FILTER_UNSAFE_RAW);

            try {
                $data = [
                    'title' => $title,
                    'text' => $text,
                    'valid_until' => $validUntil
                ];

                $success = $this->announcementService->update($id, $data);

                if ($success) {
                    SessionHelper::set('success', 'Announcement updated.');
                    $this->logger->info("Announcement updated successfully", ['id' => $id]);
                } else {
                    SessionHelper::set('error', 'No changes were made.');
                    $this->logger->warning("Announcement update made no changes", ['id' => $id]);
                }

            } catch (Exception $e) {
                $this->logger->error('Announcement update failed', [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]);
                SessionHelper::set('error', 'Announcement update failed: ' . $e->getMessage());
            }

            $this->redirect('/panel/announcements');
        }
    }

    public function addUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
            $password = trim((string)filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW));

            try {
                $this->logger->debug("add_user request received");

                if ($username === '' || $password === '') {
                    $this->logger->error("Username and password are required");
                    SessionHelper::set('error', 'Username and password are required.');
                    $this->redirect('/panel/users');
                }

                $data = [
                    'username' => $username,
                    'password' => $password
                ];

                $result = $this->userService->create($data);
                if ($result) {
                    $this->logger->info("User added successfully");
                    SessionHelper::set('success', 'User created.');
                } else {
                    $this->logger->error("User adding failed");
                    SessionHelper::set('error', 'Failed to add user.');
                }
                $this->redirect('/panel/users');
            } catch (Exception $e) {
                $this->handleError("Failed to add user", "User adding failed: " . $e->getMessage(), "/panel/users");
            }
        }
    }

    public function deleteUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $userToDelete = (int)filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

            $user = $this->getActiveUser();
            $userId = (int)$user->id;
            try {
                if ($userId === $userToDelete) {
                    SessionHelper::set('error', 'User cannot delete themselves.');
                    $this->redirect('/panel/users');
                }

                $result = $this->userService->delete($userId, $userToDelete);
                if ($result) {
                    $this->logger->info("User deleted successfully");
                    SessionHelper::set('success', 'User deleted.');
                } else {
                    $this->logger->error("User deletion failed");
                    SessionHelper::set('error', 'Failed to delete user.');
                }
                $this->redirect('/panel/users');
            } catch (Exception $e) {
                $this->handleError("User deletion failed", "User deletion failed: " . $e->getMessage(), "/panel/users");
            }
        }
    }

    public function addCountdown() : void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $title = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
            $count_to = (string)filter_input(INPUT_POST, 'count_to', FILTER_UNSAFE_RAW);

            try {
                $userId = SessionHelper::get('user_id');

                $this->logger->debug("add_countdown request received", [
                    'user_id' => $userId,
                    'title' => $title
                ]);

                if ($title === '' || $count_to === '') {
                    SessionHelper::set('error', 'All fields must be filled.');
                    $this->redirect('/panel/countdowns');
                }

                $data = [
                    'title' => $title,
                    'count_to' => $count_to
                ];

                $this->countdownService->create($data, $userId);
                SessionHelper::set('success', 'Countdown created.');
                $this->redirect('/panel/countdowns');
            } catch (Exception $e) {
                $this->handleError("Failed to add countdown", "Countdown adding failed: " . $e->getMessage(), "/panel/countdowns");
            }
        }
    }

    public function deleteCountdown(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $countdownId = (int)filter_input(INPUT_POST, 'countdown_id', FILTER_VALIDATE_INT);

            try {
                $this->countdownService->delete($countdownId);
                $this->logger->info("Countdown deleted successfully");
                SessionHelper::set('success', 'Countdown deleted.');
                $this->redirect('/panel/countdowns');
            } catch (Exception $e) {
                $this->handleError("Failed to delete countdown", "Countdown deletion failed: " . $e->getMessage(), "/panel/countdowns");
            }
        }
    }

    public function editCountdown(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $newCountdownTitle = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
            $newRawCountdownCountTo = (string)filter_input(INPUT_POST, 'count_to', FILTER_UNSAFE_RAW);
            $countdownId = (int)filter_input(INPUT_POST, 'countdown_id', FILTER_VALIDATE_INT);

            try {
                $countdown = $this->countdownService->getById($countdownId);
                if (empty($countdown)) {
                    throw new Exception("Countdown not found");
                }

                $dt = DateTime::createFromFormat('Y-m-d\TH:i', $newRawCountdownCountTo);
                if (!$dt) {
                    throw new Exception("Invalid date format");
                }
                $newCountdownCountTo = $dt->format('Y-m-d H:i:s');

                $updates = [];

                if ($newCountdownTitle !== '' && $newCountdownTitle !== $countdown->title) {
                    $updates['title'] = $newCountdownTitle;
                }

                if ($newCountdownCountTo != $countdown->countTo) {
                    $updates['count_to'] = $newCountdownCountTo;
                }

                if (!empty($updates)) {
                    $this->countdownService->update($countdownId, $updates);
                    $this->logger->debug("Updated countdown fields", ['countdown_id' => $countdownId, 'updates' => $updates]);
                    SessionHelper::set('success', 'Countdown updated.');
                } else {
                    SessionHelper::set('error', 'No changes were made.');
                }

                $this->redirect('/panel/countdowns');
            } catch (Exception $e) {
                $this->handleError("Failed to update countdown", "Countdown update failed: " . $e->getMessage(), "/panel/countdowns");
            }
        }
    }

    public function toggleModule(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);
            $enable = filter_input(INPUT_POST, 'is_active', FILTER_UNSAFE_RAW);

            try {
                if (!$moduleId || !isset($enable)) {
                    $this->redirect("/panel");
                }
                $this->moduleService->toggle($moduleId);
                $this->redirect("/panel");
            } catch (Exception $e) {
                $this->handleError("Failed to toggle module", "Failed to toggle module: " . $e->getMessage(), "/panel");
            }
        }
    }

    public function editModule(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);
            $newModuleStartTime = trim((string)filter_input(INPUT_POST, 'start_time', FILTER_UNSAFE_RAW));
            $newModuleEndTime = trim((string)filter_input(INPUT_POST, 'end_time', FILTER_UNSAFE_RAW));
            $newModuleIsActive = isset($_POST['is_active']) ? 1 : 0;

            try {
                $module = $this->moduleService->getById($moduleId);

                if (empty($module)) {
                    throw new Exception("Module not found");
                }

                $dateFormat = $this->config->modulesDateFormat ?? 'H:i';

                $normalizedStart = $this->normalizeTime($newModuleStartTime, $module->startTime, $dateFormat);
                $normalizedEnd = $this->normalizeTime($newModuleEndTime, $module->endTime, $dateFormat);

                $updates = [
                    'module_name' => $module->moduleName,
                    'is_active' => $newModuleIsActive,
                    'start_time' => $normalizedStart,
                    'end_time' => $normalizedEnd,
                ];

                $this->moduleService->update($moduleId, $updates);

                $this->redirect('/panel/modules');
            } catch (Exception $e) {
                $this->handleError("Failed to edit module", "Module edit failed " . $e->getMessage(), "/panel/modules");
            }
        }
    }

    private function normalizeTime(string $value, DateTimeImmutable $fallback, string $dateFormat): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback->format($dateFormat);
        }
        $candidates = ['H:i', 'H:i:s'];
        foreach ($candidates as $fmt) {
            $dt = DateTimeImmutable::createFromFormat($fmt, $value);
            if ($dt instanceof DateTimeImmutable) {
                return $dt->format($dateFormat);
            }
        }
        try {
            $dt = new DateTimeImmutable($value);
            return $dt->format($dateFormat);
        } catch (Throwable) {
            return $fallback->format($dateFormat);
        }
    }
}
