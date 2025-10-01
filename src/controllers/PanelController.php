<?php

namespace src\controllers;

use DateTime;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use src\core\Controller;
use src\models\AnnouncementsModel;
use src\models\CountdownModel;
use src\models\ModuleModel;
use src\models\UserModel;
use src\core\SessionHelper;

/**
 * User controller
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 */
class PanelController extends Controller
{
    function __construct(
        private readonly LoggerInterface $logger,
        private readonly ModuleModel $moduleModel,
        private readonly AnnouncementsModel $announcementsModel,
        private readonly UserModel $userModel,
        private readonly CountdownModel $countdownModel,
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
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== SessionHelper::get('csrf_token')) {
            $this->logger->error("Nieprawidłowy token CSRF.");
            SessionHelper::set('error', 'Nieprawidłowy token CSRF.');
            header("Location: /login");
            exit;
        }
    }

    public function index(): void
    {
        $userId = SessionHelper::get('user_id');
        if (!$userId) {
            header("Location: /login");
            exit;
        }

        try {
            $user = $this->userModel->getUserById($userId);

            $showOnlyValid = $_SESSION['display_valid_announcements_only'] ?? false;
            $announcements = $showOnlyValid
                ? $this->announcementsModel->getValidAnnouncements()
                : $this->announcementsModel->getAnnouncements();
            $users = $this->userModel->getUsers();
            $modules = $this->moduleModel->getModules();
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
        $userId = SessionHelper::get('user_id');

        if (!$userId) {
            header("Location: /login");
            exit;
        }

        try {
            $user = $this->userModel->getUserById($userId);
            $users = $this->userModel->getUsers();
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
        $userId = SessionHelper::get('user_id');
        if (!$userId) {
            header("Location: /login");
            exit;
        }

        try {
            $user = $this->userModel->getUserById($userId);
            $users = $this->userModel->getUsers();
            $countdowns = $this->countdownModel->getCountdowns();
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
        $userId = SessionHelper::get('user_id');
        if (!$userId) {
            header("Location: /login");
            exit;
        }
        $user = $this->userModel->getUserById($userId);
        $users = $this->userModel->getUsers();
        $announcements = $this->announcementsModel->getAnnouncements();
        $this->render('announcements', [
            'user' => $user,
            'users' => $users,
            'announcements' => $announcements
        ]);
    }

    public function modules(): void
    {
        $userId = SessionHelper::get('user_id');
        if (!$userId) {
            header("Location: /login");
            exit;
        }
        $user = $this->userModel->getUserById($userId);
        $modules = $this->moduleModel->getModules();
        $this->render('modules', [
            'user' => $user,
            'modules' => $modules
        ]);
    }

    #[NoReturn] public function logout(): void
    {
        SessionHelper::destroy();
        header("Location: /login");
        exit;
    }

    public function login(): void
    {
        $this->setCsrf();
        $this->render('login');
    }

    #[NoReturn] public function authenticate(): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->logger->debug("Rozpoczęto weryfikację użytkownika.");
            $this->checkCsrf();

            $username = trim($_POST['username']) ?? '';
            $password = trim($_POST['password']) ?? '';

            if (empty($password) or empty($username)) {
                $this->logger->error("Password or username cannot be null!");
                SessionHelper::set("error", "Password or username cannot be null!");
                header("Location: /login");
            }

            $user = $this->userModel->getUserByUsername($username);

            if ($user && password_verify($password, $user[0]['password'])) {
                $this->logger->info("Prawidłowe hasło dla podanej nazwy użytkownika!");
                $this->setCsrf();
                SessionHelper::set('user_id', $user[0]['id']);
                header("Location: /panel");
            } else {
                $this->logger->error("Nieprawidłowe hasło dla podanej nazwy użytkownika!");
                SessionHelper::set('error', 'Incorrect username or password!');
                header("Location: /login");
            }
            exit;
        }
        $this->logger->error("Nieprawidłowa metoda HTTP!");
        header("Location: /login");
        exit;
    }

    public function deleteAnnouncement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->logger->debug("delete_announcement request received");
                $this->checkCsrf();

                $announcementId = $_POST['announcement_id'];
                $userId = SessionHelper::get('user_id');


                $result = $this->announcementsModel->deleteAnnouncement($announcementId, $userId);

                if ($result) {
                    $this->logger->info("Announcement deleted");
                } else {
                    $this->logger->error("Announcement could not be deleted");
                    SessionHelper::set('error', 'Failed to delete announcement');
                }
                header('Location: /panel/announcements');
                exit;
            } catch (Exception $e) {
                $this->logger->error('Announcement deletion failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'An error occurred while deleting the announcement');
                header('Location: /panel/announcements');
                exit;
            }
        }
    }

    public function addAnnouncement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
            try {
                $this->logger->debug("add_announcement request received");
                $this->checkCsrf();

                $title = isset($_POST['title']) ? trim($_POST['title']) : '';
                $text = isset($_POST['text']) ? trim($_POST['text']) : '';
                $validUntil = $_POST['valid_until'];
                $userId = SessionHelper::get('user_id');


                $result = $this->announcementsModel->addAnnouncement($title, $text, $validUntil, $userId);
                if ($result) {
                    $this->logger->info("Announcement added successfully");
                } else {
                    $this->logger->error("Announcement adding failed");
                    $_SESSION['error'] = 'Failed to add announcement';
                }
                header('Location: /panel/announcements');
                exit;
            } catch (Exception $e) {
                $this->logger->error('Announcement adding failed', ['error' => $e->getMessage()]);
                $_SESSION['error'] = 'Failed to add announcement';
                header('Location: /panel/announcements');
                exit;
            }
        }
    }

    public function editAnnouncement(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->logger->debug("edit_announcement request received");
                $this->checkCsrf();

                $newAnnouncementTitle = trim($_POST['title']);
                $newAnnouncementText = trim($_POST['text']);
                $newAnnouncementValidUntil = $_POST['valid_until'];

                if (empty($newAnnouncementTitle) || empty($newAnnouncementText)) {
                    SessionHelper::set('error', 'All fields must be filled.');
                    header('Location: /panel/announcements');
                    exit;
                }

                $userId = SessionHelper::get('user_id');

                $announcementId = $_POST['announcement_id'];
                $announcement = $this->announcementsModel->getAnnouncementById($announcementId);


                $updates = [];

                if ($newAnnouncementTitle !== $announcement[0]['title']) {
                    $updates['title'] = $newAnnouncementTitle;
                }
                if ($newAnnouncementText !== $announcement[0]['text']) {
                    $updates['text'] = $newAnnouncementText;
                }
                if ($newAnnouncementValidUntil !== $announcement[0]['valid_until']) {
                    $updates['valid_until'] = $newAnnouncementValidUntil;
                }

                foreach ($updates as $field => $value) {
                    $this->announcementsModel->updateAnnouncementField($announcementId, $field, $value, $userId);
                    $this->logger->debug("Updated field: $field", ['announcement_id' => $announcementId]);
                }

                header('Location: /panel/announcements');
                exit;
            } catch (Exception $e) {
                $this->logger->error('Announcement update failed', [
                    'announcement_id' => $announcementId,
                    'error' => $e->getMessage()
                ]);
                SessionHelper::set('error', 'Announcement update failed');
                header('Location: /panel/announcements');
                exit;
            }

        }
    }

    public function addUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
            try {
                $this->logger->debug("add_user request received");
                $this->checkCsrf();

                if (!isset($_POST['username']) || !isset($_POST['password']) || empty(trim($_POST['username'])) || empty(trim($_POST['password']))) {
                    $this->logger->error("Username and password are required");
                    SessionHelper::set('error', 'Username and password are required!');
                    header('Location: /panel/users');
                    exit;
                }

                $username = trim($_POST['username']);
                $password = trim($_POST['password']);


                $result = $this->userModel->addUser($username, $password);
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
            try {
                $this->logger->debug("delete_user request received");
                $this->checkCsrf();

                $userToDelete = trim($_POST['user_id']);


                $result = $this->userModel->deleteUser($userToDelete);
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
            try {
                $this->checkCsrf();

                $title = trim($_POST['title']);
                $count_to = $_POST['count_to'];
                $userId = SessionHelper::get('user_id');

                if (empty($title) || empty($count_to)) {
                    SessionHelper::set('error', 'All fields must be filled.');
                }

                $this->countdownModel->addCountdown($title, $count_to, $userId);
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

            $countdownId = $_POST['countdown_id'];

            try {
                $this->countdownModel->deleteCountdown($countdownId);
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

            $newCountdownTitle = trim($_POST['title']);
            $newRawCountdownCountTo = $_POST['count_to'];
            $countdownId = $_POST['countdown_id'];

            $userId = SessionHelper::get('user_id');

            $countdown = $this->countdownModel->getCountdownById($countdownId);

            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $newRawCountdownCountTo);
            $newCountdownCountTo = $dt->format('Y-m-d H:i:s');

            try {
                $updates = [];

                if ($newCountdownTitle !== $countdown[0]['title']) {
                    $updates['title'] = $newCountdownTitle;
                }

                if ($newCountdownCountTo !== $countdown[0]['count_to']) {
                    $updates['count_to'] = $newCountdownCountTo;
                }

                foreach ($updates as $field => $value) {
                    $this->countdownModel->updateCountdownField($countdownId, $field, $value, $userId);
                    $this->logger->debug("Updated field: $field", ['countdown_id' => $countdownId]);
                }

                $this->logger->info("Countdown updated successfully");
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

    public function toggleModule(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();

            $moduleId = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;
            $enable = isset($_POST['enable']) ? filter_var($_POST['enable'], FILTER_VALIDATE_BOOLEAN) : null;

            if ($moduleId <= 0 || $enable === null) {
                header("Location: /panel");
                exit;
            }

            try {
                $this->moduleModel->toggleModule($moduleId, $enable);
                $action = $enable ? 'włączony' : 'wyłączony';
                $this->logger->info("Moduł $moduleId został $action");
            } catch (Exception $e) {
                $this->logger->error("Błąd przy " . ($enable ? 'włączaniu' : 'wyłączaniu') . " modułu", ['error' => $e->getMessage()]);
            }
            header("Location: /panel");
            exit;
        }
    }

    /**
     * @throws Exception
     */
    public function editModule(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();

            $newModuleStartTime = $_POST['start_time'] ?? '';
            $newModuleEndTime = $_POST['end_time'] ?? '';
            $moduleId = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;
            $newModuleIsActive = isset($_POST['is_active']) ? 1 : 0;

            $module = $this->moduleModel->getModuleById($moduleId);

            if (empty($module)) {
                throw new Exception("Module not found");
            }

            try {
                $updates = [];

                if ($newModuleStartTime !== '' && $newModuleStartTime !== $module['start_time']) {
                    $updates['start_time'] = $newModuleStartTime;
                }

                if ($newModuleEndTime !== '' && $newModuleEndTime !== $module['end_time']) {
                    $updates['end_time'] = $newModuleEndTime;
                }

                if ((int)$newModuleIsActive !== (int)$module['is_active']) {
                    $this->moduleModel->toggleModule($moduleId, (bool)$newModuleIsActive);
                }

                foreach ($updates as $field => $value) {
                    $this->moduleModel->updateModuleField($moduleId, $field, $value);
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