<?php

namespace src\controllers;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use src\core\CommonService;
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
    private UserModel $userModel;
    private AnnouncementsModel $announcementsModel;
    private ModuleModel $moduleModel;
    private CountdownModel $countdownModel;

    function __construct()
    {
        SessionHelper::start();
        CommonService::initLogger();
        $this->userModel = new UserModel();
        $this->announcementsModel = new AnnouncementsModel();
        $this->moduleModel = new ModuleModel();
        $this->countdownModel = new CountdownModel();
    }

    private function setCsrf(): void
    {
        if (!SessionHelper::has('csrf_token') || empty(SessionHelper::get('csrf_token'))) {
            SessionHelper::set('csrf_token', bin2hex(random_bytes(32)));
        }
    }

    private function checkCsrf(): void
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== SessionHelper::get('csrf_token')) {
            self::$logger->error("Nieprawidłowy token CSRF.");
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
        $user = $this->userModel->getUserById($userId);

        $showOnlyValid = $_SESSION['display_valid_announcements_only'] ?? false;
        $announcements = $showOnlyValid
            ? $this->announcementsModel->getValidAnnouncements()
            : $this->announcementsModel->getAnnouncements();
        $users = $this->userModel->getUsers();
        $modules = $this->moduleModel->getModules();

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
        $user = $this->userModel->getUserById($userId);
        $users = $this->userModel->getUsers();
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
        $user = $this->userModel->getUserById($userId);
        $countdowns = $this->countdownModel->getCountdowns();
        $this->render('countdowns', [
            'user' => $user,
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
        $announcements = $this->announcementsModel->getAnnouncements();
        $this->render('announcements', [
            'user' => $user,
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
            self::$logger->debug("Rozpoczęto weryfikację użytkownika.");
            $this->checkCsrf();

            $username = trim($_POST['username']) ?? '';
            $password = trim($_POST['password']) ?? '';

            if (empty($password) or empty($username)) {
                self::$logger->error("Password or username cannot be null!");
                SessionHelper::set("error", "Password or username cannot be null!");
                header("Location: /login");
            }

            $user = $this->userModel->getUserByUsername($username);

            if ($user && password_verify($password, $user[0]['password'])) {
                self::$logger->info("Prawidłowe hasło dla podanej nazwy użytkownika!");
                $this->setCsrf();
                SessionHelper::set('user_id', $user[0]['id']);
                header("Location: /panel");
            } else {
                self::$logger->error("Nieprawidłowe hasło dla podanej nazwy użytkownika!");
                SessionHelper::set('error', 'Incorrect username or password!');
                header("Location: /login");
            }
            exit;
        }
        self::$logger->error("Nieprawidłowa metoda HTTP!");
        header("Location: /login");
        exit;
    }

    public function deleteAnnouncement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::$logger->debug("delete_announcement request received");
            $this->checkCsrf();

            $announcementId = $_POST['announcement_id'];
            $userId = SessionHelper::get('user_id');

            try {
                $result = $this->announcementsModel->deleteAnnouncement($announcementId, $userId);

                if ($result) {
                    self::$logger->info("Announcement deleted");
                } else {
                    self::$logger->error("Announcement could not be deleted");
                    SessionHelper::set('error', 'Failed to delete announcement');
                }
                header('Location: /panel/announcements');
                exit;
            } catch (Exception $e) {
                self::$logger->error('Announcement deletion failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'An error occurred while deleting the announcement');
                header('Location: /panel/announcements');
                exit;
            }
        }
    }

    public function addAnnouncement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
            self::$logger->debug("add_announcement request received");
            $this->checkCsrf();

            $title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $text = isset($_POST['text']) ? trim($_POST['text']) : '';
            $validUntil = $_POST['valid_until'];
            $userId = SessionHelper::get('user_id');

            try {
                $result = $this->announcementsModel->addAnnouncement($title, $text, $validUntil, $userId);
                if ($result) {
                    self::$logger->info("Announcement added successfully");
                } else {
                    self::$logger->error("Announcement adding failed");
                    $_SESSION['error'] = 'Failed to add announcement';
                }
                header('Location: /panel/announcements');
                exit;
            } catch (Exception $e) {
                self::$logger->error('Announcement adding failed', ['error' => $e->getMessage()]);
                $_SESSION['error'] = 'Failed to add announcement';
                header('Location: /panel/announcements');
                exit;
            }
        }
    }

    public function editAnnouncement(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::$logger->debug("edit_announcement request received");
            $this->checkCsrf();

            $newAnnouncementTitle = trim($_POST['title']);
            $newAnnouncementText = trim($_POST['text']);
            $newAnnouncementValidUntil = $_POST['valid_until'];

            $userId = SessionHelper::get('user_id');

            $announcementId = $_POST['announcement_id'];
            $announcement = $this->announcementsModel->getAnnouncementById($announcementId);

            try {
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
                    self::$logger->debug("Updated field: $field", ['announcement_id' => $announcementId]);
                }

                header('Location: /panel/announcements');
                exit;
            } catch (Exception $e) {
                self::$logger->error('Announcement update failed', [
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
            self::$logger->debug("add_user request received");
            $this->checkCsrf();

            if (!isset($_POST['username']) || !isset($_POST['password']) || empty(trim($_POST['username'])) || empty(trim($_POST['password']))) {
                self::$logger->error("Username and password are required");
                SessionHelper::set('error', 'Username and password are required!');
                header('Location: /panel/users');
                exit;
            }


            $username = trim($_POST['username']);
            $password = trim($_POST['password']);

            try {
                $result = $this->userModel->addUser($username, $password);
                if ($result) {
                    self::$logger->info("User added successfully");
                } else {
                    self::$logger->error("User adding failed");
                    SessionHelper::set('error', 'Failed to add user');
                }
                header('Location: /panel/users');
                exit;
            } catch (Exception $e) {
                self::$logger->error('User adding failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Failed to add user');
                header('Location: /panel/users');
                exit;
            }
        }
    }

    public function deleteUser(): void
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_to_delete_id'])) {
            self::$logger->debug("delete_user request received");
            $this->checkCsrf();

            $userToDelete = trim($_POST['user_to_delete_id']);

            try {
                $result = $this->userModel->deleteUser($userToDelete);
                if ($result) {
                    self::$logger->info("User deleted successfully");
                } else {
                    self::$logger->error("User deletion failed");
                    SessionHelper::set('error', 'Failed to delete user');
                }
                header('Location: /panel/users');
                exit;
            } catch (Exception $e) {
                self::$logger->error('User deletion failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Failed to delete user');
                header('Location: /panel/users');
                exit;
            }
        }
    }

    public function editUser(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();

            //TODO
        }
    }

    public function addCountdown() : void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();

            $title = trim($_POST['title']);
            $count_to = $_POST['count_to'];
            $userId = SessionHelper::get('user_id');

            if (empty($title) || empty($count_to)) {
                SessionHelper::set('error', 'All fields must be filled.');
            }

            try {
                $this->countdownModel->addCountdown($title, $count_to);
                self::$logger->info("Countdown added successfully");
                header('Location: /panel/countdowns');;
                exit;
            } catch (Exception $e) {
                self::$logger->error('Countdown adding failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Failed to add countdown');;
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
                self::$logger->info("Countdown deleted successfully");
                header('Location: /panel/countdowns');;
                exit;
            } catch (Exception $e) {
                self::$logger->error('Countdown deletion failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Failed to delete countdown');;
                header('Location: /panel/countdowns');
                exit;
            }
        }
    }

    public function editCountdown(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();

            $newCountdownTitle = trim($_POST['title']);
            $newCountdownCountTo = $_POST['count_to'];
            $countdownId = $_POST['countdown_id'];

            $userId = SessionHelper::get('user_id');

            $countdown = $this->countdownModel->getCountdownById($countdownId);

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
                    self::$logger->debug("Updated field: $field", ['announcement_id' => $countdownId]);
                }

                self::$logger->info("Countdown updated successfully");
                header('Location: /panel/countdowns');
                exit;
            } catch (Exception $e) {
                self::$logger->error('Countdown adding failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Failed to add countdown');;
                header('Location: /panel/countdowns');
                exit;
            }
        }
    }

    public function toggleModule(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $moduleName = $_POST['module_name'] ?? '';
            $enable = isset($_POST['enable']) ? filter_var($_POST['enable'], FILTER_VALIDATE_BOOLEAN) : null;

            if ($moduleName === '' || $enable === null) {
                header("Location: /panel");
                exit;
            }

            try {
                $this->moduleModel->toggleModule($moduleName, $enable);
                $action = $enable ? 'włączony' : 'wyłączony';
                self::$logger->info("Moduł $moduleName został $action");
            } catch (Exception $e) {
                self::$logger->error("Błąd przy " . ($enable ? 'włączaniu' : 'wyłączaniu') . " modułu", ['error' => $e->getMessage()]);
            }
            header("Location: /panel");
            exit;
        }
    }
}
