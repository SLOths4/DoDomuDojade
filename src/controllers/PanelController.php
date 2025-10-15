<?php

namespace src\controllers;

use DateTime;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use src\core\Controller;
use src\infrastructure\helpers\SessionHelper;
use src\models\CountdownModel;
use src\models\ModuleModel;
use src\service\AnnouncementService;
use src\service\CountdownService;
use src\service\ModuleService;
use src\service\UserService;

/**
 * User controller
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
        if (!SessionHelper::has('csrf_token') || empty(SessionHelper::get('csrf_token'))) {
            SessionHelper::set('csrf_token', bin2hex(random_bytes(32)));
        }
    }

    private function checkCsrf(): void
    {
        try {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== SessionHelper::get('csrf_token')) {
                $this->logger->error("Nieprawidłowy token CSRF.");
                SessionHelper::set('error', 'Nieprawidłowy token CSRF.');
                header("Location: /login");
                exit;
            }
        } catch (Exception $e) {
            $this->logger->error("Csrf error occurred: ". $e->getMessage());
            $this->errorController->internalServerError();
        }
    }

    private function checkIsUserLoggedIn(): void
    {
        $userId = SessionHelper::get('user_id');
        if (!$userId) {
            header("Location: /login");
            exit;
        }
    }

    public function index(): void
    {
        try {
            $this->checkIsUserLoggedIn();

            $userId = SessionHelper::get('user_id');
            $user = $this->userService->getById($userId);

            $showOnlyValid = $_SESSION['display_valid_announcements_only'] ?? false;
            $announcements = $showOnlyValid
                ? $this->announcementService->getValid()
                : $this->announcementService->getAll();
            $users = $this->userService->getAll();
            $modules = $this->moduleService->getAll();
        } catch (Exception $e) {
            $this->logger->error("Błąd podczas ładowania strony głównej: " . $e->getMessage());

            SessionHelper::set('error', 'Nie udało się załadować strony głównej.');

            header("Location: /login");
            exit;
        }

        $this->render('panel', [
            'user' => $user,
            'announcements' => $announcements,
            'users' => $users,
            'modules' => $modules
        ]);
    }

    public function users(): void
    {
        try {
            $this->checkIsUserLoggedIn();

            $userId = SessionHelper::get('user_id');

            $user = $this->userService->getById($userId);
            $users = $this->userService->getAll();
        } catch (Exception $e) {
            $this->logger->error("Błąd podczas ładowania strony users: " . $e->getMessage());

            SessionHelper::set('error', 'Nie udało się załadować strony users.');

            header("Location: /login");
            exit;
        }

        $this->render('users', [
            'user' => $user,
            'users' => $users
        ]);
    }

    public function countdowns(): void
    {
        try {
            $this->checkIsUserLoggedIn();

            $userId = SessionHelper::get('user_id');

            $user = $this->userService->getById($userId);
            $users = $this->userService->getAll();
            $countdowns = $this->countdownService->getAll();
        } catch (Exception $e) {
            $this->logger->error("Błąd podczas ładowania strony odliczań: " . $e->getMessage());

            SessionHelper::set('error', 'Nie udało się załadować strony odliczania.');

            header("Location: /login");
            exit;
        }

        $this->render('countdowns', [
            'user' => $user,
            'users' => $users,
            'countdowns' => $countdowns
        ]);
    }

    public function announcements(): void
    {
        try {
            $this->checkIsUserLoggedIn();

            $userId = SessionHelper::get('user_id');

            $user = $this->userService->getById($userId);
            $users = $this->userService->getAll();
            $announcements = $this->announcementService->getAll();

            $this->render('announcements', [
                'user' => $user,
                'users' => $users,
                'announcements' => $announcements
            ]);
        } catch (Exception $e) {
            $this->logger->error("An error occurred: ".$e->getMessage());
            $this->errorController->internalServerError();
        }
    }

    public function modules(): void
    {
        try {
            $this->checkIsUserLoggedIn();

            $userId = SessionHelper::get('user_id');

            $user = $this->userService->getById($userId);
            $modules = $this->moduleService->getAll();
            $this->render('modules', [
                'user' => $user,
                'modules' => $modules
            ]);
        } catch (Exception $e) {
            $this->logger->error("An error occurred: ".$e->getMessage());
            $this->errorController->internalServerError();
        }
    }

    #[NoReturn] public function logout(): void
    {
        SessionHelper::destroy();
        header("Location: /login");
        exit;
    }

    public function login(): void
    {
        try {
            $this->setCsrf();
            $this->render('login');
        } catch (Exception $e) {
            $this->logger->error("An error occurred: ".$e->getMessage());
            $this->errorController->internalServerError();
        }
    }

    #[NoReturn] public function authenticate(): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            try {
                $this->logger->debug("Rozpoczęto weryfikację użytkownika.");
                $this->checkCsrf();

                $username = trim($_POST['username']) ?? '';
                $password = trim($_POST['password']) ?? '';

                if (empty($password) or empty($username)) {
                    $this->logger->error("Password or username cannot be null!");
                    SessionHelper::set("error", "Pola nazwy użytkownika i hasła muszą być wypełnione!");
                    header("Location: /login");
                }

                $user = $this->userService->getByExactUsername($username);

                if ($user && password_verify($password, $user[0]['password'])) {
                    $this->logger->debug("Correct password for given username.");
                    $this->setCsrf();
                    SessionHelper::set('user_id', $user[0]['id']);
                    header("Location: /panel");
                } else {
                    $this->logger->debug("Incorrect password for given username!");
                    SessionHelper::set('error', 'Nieprawidłowa nazwa użytkownika lub hasło!');
                    header("Location: /login");
                }
                exit;
            } catch (Exception $e) {
                $this->logger->error("An error occurred: ".$e->getMessage());
                $this->errorController->internalServerError();
            }
        }
        $this->logger->error("Nieprawidłowa metoda HTTP!");
        header("Location: /login");
        exit;
    }

    public function deleteAnnouncement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $announcementId = $_POST['announcement_id'];

            try {
                $this->logger->debug("delete_announcement request received");

                $result = $this->announcementService->delete($announcementId);

                if ($result) {
                    $this->logger->info("Announcement deleted");
                    SessionHelper::set('success', 'Announcement deleted successfully');
                } else {
                    $this->logger->error("Announcement could not be deleted");
                    SessionHelper::set('error', 'Failed to delete announcement');
                }
                header('Location: /panel/announcements');
                exit;
            } catch (Exception $e) {
                $this->logger->error('Announcement deletion failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Wystąpił błąd w trakcie usuwania ogłoszenia.');
                header('Location: /panel/announcements');
                exit;
            }
        }
    }

    public function addAnnouncement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /panel/announcements');
            exit;
        }

        $this->checkCsrf();
        $this->checkIsUserLoggedIn();

        $title = trim($_POST['title'] ?? '');
        $text = trim($_POST['text'] ?? '');
        $validUntil = $_POST['valid_until'] ?? '';

        $userId = SessionHelper::get('user_id');

        try {
            $this->logger->debug("Add announcement request received", [
                'user_id' => $userId,
                'title' => $title
            ]);

            $data = [
                'title' => $title,
                'text' => $text,
                'valid_until' => $validUntil
            ];

            $success = $this->announcementService->create($data, $userId);

            if ($success) {
                $_SESSION['success'] = 'Announcement added successfully';
                $this->logger->info("Announcement added successfully", ['user_id' => $userId]);
            } else {
                $_SESSION['error'] = 'Failed to add announcement';
                $this->logger->warning("Announcement add failed", ['user_id' => $userId]);
            }

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error adding announcement: ' . $e->getMessage();
            $this->logger->error("Exception while adding announcement", ['error' => $e->getMessage()]);
        }

        header('Location: /panel/announcements');
        exit;
    }

    public function editAnnouncement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /panel/announcements');
            exit;
        }

        $this->checkCsrf();
        $this->checkIsUserLoggedIn();

        $id = (int)($_POST['announcement_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $text = trim($_POST['text'] ?? '');
        $validUntil = $_POST['valid_until'] ?? '';

        if (empty($id) || empty($title) || empty($text) || empty($validUntil)) {
            SessionHelper::set('error', 'All fields must be filled.');
            header('Location: /panel/announcements');
            exit;
        }

        try {
            $this->logger->debug("edit_announcement request received", ['id' => $id]);

            $data = [
                'title' => $title,
                'text' => $text,
                'valid_until' => $validUntil
            ];

            $success = $this->announcementService->update($id, $data);

            if ($success) {
                SessionHelper::set('success', 'Announcement updated successfully.');
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

        header('Location: /panel/announcements');
        exit;
    }

    public function addUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            try {
                $this->logger->debug("add_user request received");

                if (!isset($_POST['username']) || !isset($_POST['password']) || empty(trim($_POST['username'])) || empty(trim($_POST['password']))) {
                    $this->logger->error("Username and password are required");
                    SessionHelper::set('error', 'Username and password are required!');
                    header('Location: /panel/users');
                    exit;
                }

                $username = trim($_POST['username']);
                $password = trim($_POST['password']);

                $data = [
                    $username,
                    $password
                ];

                $result = $this->userService->create($data);
                if ($result) {
                    $this->logger->info("User added successfully");
                } else {
                    $this->logger->error("User adding failed");
                    SessionHelper::set('error', 'Failed to add user');
                }
                header('Location: /panel/users');
                exit;
            } catch (Exception $e) {
                $this->logger->error('User adding failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Failed to add user');
                header('Location: /panel/users');
                exit;
            }
        }
    }

    public function deleteUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $userId = SessionHelper::get('user_id');
            $userToDelete = trim($_POST['user_id']);
            try {
                if ($userId == $userToDelete) {
                    SessionHelper::set('error', 'Użytkownik nie może usunąć sam siebie!');
                    header('Location: /panel/users');
                    exit;
                }

                $result = $this->userService->delete($userToDelete);
                if ($result) {
                    $this->logger->info("User deleted successfully");
                } else {
                    $this->logger->error("User deletion failed");
                    SessionHelper::set('error', 'Failed to delete user');
                }
                header('Location: /panel/users');
                exit;
            } catch (Exception $e) {
                $this->logger->error('User deletion failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Failed to delete user');
                header('Location: /panel/users');
                exit;
            }
        }
    }

    public function addCountdown() : void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $title = trim($_POST['title']);
            $count_to = $_POST['count_to'];

            try {
                $userId = SessionHelper::get('user_id');

                if (empty($title) || empty($count_to)) {
                    SessionHelper::set('error', 'All fields must be filled.');
                }

                $data = [
                    $title,
                    $count_to
                ];

                $this->countdownService->create($data, $userId);
                $this->logger->info("Countdown added successfully");
                header('Location: /panel/countdowns');
                exit;
            } catch (Exception $e) {
                $this->logger->error('Countdown adding failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Failed to add countdown');
                header('Location: /panel/countdowns');
                exit;
            }
        }
    }

    public function deleteCountdown(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $countdownId = $_POST['countdown_id'];

            try {
                $this->countdownService->delete($countdownId);
                $this->logger->info("Countdown deleted successfully");
                header('Location: /panel/countdowns');
                exit;
            } catch (Exception $e) {
                $this->logger->error('Countdown deletion failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Failed to delete countdown');
                header('Location: /panel/countdowns');
                exit;
            }
        }
    }

    public function editCountdown(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $newCountdownTitle = trim($_POST['title']);
            $newRawCountdownCountTo = $_POST['count_to'];
            $countdownId = $_POST['countdown_id'];

            $userId = SessionHelper::get('user_id');

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

                if ($newCountdownTitle !== $countdown[0]['title']) {
                    $updates['title'] = $newCountdownTitle;
                }

                if ($newCountdownCountTo !== $countdown[0]['count_to']) {
                    $updates['count_to'] = $newCountdownCountTo;
                }

                if (!empty($updates)) {
                    $this->countdownService->update($countdownId, $updates);
                    $this->logger->debug("Updated countdown fields", ['countdown_id' => $countdownId, 'updates' => $updates]);
                }

                $this->logger->info("Countdown updated successfully");
                header('Location: /panel/countdowns');
                exit;
            } catch (Exception $e) {
                $this->logger->error('Countdown update failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Failed to update countdown');
                header('Location: /panel/countdowns');
                exit;
            }
        }
    }


    public function toggleModule(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $moduleId = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;
            $enable = isset($_POST['enable']) ? filter_var($_POST['enable'], FILTER_VALIDATE_BOOLEAN) : null;

            try {

                if ($moduleId <= 0 || $enable === null) {
                    header("Location: /panel");
                    exit;
                }

                $this->moduleService->toggle($moduleId, $enable);
                $action = $enable ? 'włączony' : 'wyłączony';
                $this->logger->info("Moduł $moduleId został $action");
            } catch (Exception $e) {
                $this->logger->error("Błąd przy " . ($enable ? 'włączaniu' : 'wyłączaniu') . " modułu", ['error' => $e->getMessage()]);
            }
            header("Location: /panel");
            exit;
        }
    }

    public function editModule(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $newModuleStartTime = $_POST['start_time'] ?? '';
            $newModuleEndTime = $_POST['end_time'] ?? '';
            $moduleId = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;
            $newModuleIsActive = isset($_POST['is_active']) ? 1 : 0;

            try {


                $module = $this->moduleService->getModuleById($moduleId);

                if (empty($module)) {
                    throw new Exception("Module not found");
                }

                $updates = [];

                if ($newModuleStartTime !== '' && $newModuleStartTime !== $module['start_time']) {
                    $updates['start_time'] = $newModuleStartTime;
                }

                if ($newModuleEndTime !== '' && $newModuleEndTime !== $module['end_time']) {
                    $updates['end_time'] = $newModuleEndTime;
                }

                if ($newModuleIsActive !== (int)$module['is_active']) {
                    $this->moduleService->toggleModule($moduleId, (bool)$newModuleIsActive);
                }

                foreach ($updates as $field => $value) {
                    $this->moduleService->updateModuleField($moduleId, $field, $value);
                    $this->logger->debug("Updated field: $field", ['module_id' => $moduleId]);
                }

                $this->logger->info("Module updated successfully");
                header('Location: /panel/modules');
                exit;
            } catch (Exception $e) {
                $this->logger->error('Module edit failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Failed to edit module');
                header('Location: /panel/modules');
                exit;
            }
        }
    }

}