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
            $this->handleError("No user logged in", "/login");
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
    private function handleError(string $message, string $redirectTo = '/login'): void
    {
        $this->logger->error($message);
        SessionHelper::set('error', $message);
        header("Location: $redirectTo");
        exit;
    }

    public function index(): void
    {
        try {
            $user = $this->getActiveUser();

            $showOnlyValid = $_SESSION['display_valid_announcements_only'] ?? false;
            $announcements = $showOnlyValid
                ? $this->announcementService->getValid()
                : $this->announcementService->getAll();
            $users = $this->userService->getAll();
            $modules = $this->moduleService->getAll();

            $this->render('panel', [
                'user' => $user,
                'announcements' => $announcements,
                'users' => $users,
                'modules' => $modules
            ]);
        } catch (Exception $e) {
            $this->logger->error("Error while loading index: " . $e->getMessage());
            SessionHelper::set('error', 'Nie udało się załadować strony głównej.');
            header("Location: /login");
            exit;
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
            $this->logger->error("Błąd podczas ładowania strony users: " . $e->getMessage());
            SessionHelper::set('error', 'Nie udało się załadować strony users.');
            header("Location: /login");
            exit;
        }
    }

    public function countdowns(): void
    {
        try {
            $user = $this->getActiveUser();
            $users = $this->userService->getAll();
            $countdowns = $this->countdownService->getAll();

            $usernames = [];
            foreach ($users as $u) {
                $usernames[$u->id] = $u->username;
            }

            $formattedCountdowns = [];
            foreach ($countdowns as $countdown) {
                $formattedCountdowns[] = (object) [
                    'id' => $countdown->id,
                    'title' => $countdown->title,
                    'userId' => $countdown->userId,
                    'countTo' => $countdown->countTo instanceof \DateTimeImmutable 
                        ? $countdown->countTo->format('Y-m-d') 
                        : $countdown->countTo,
                ];
            }

            $this->render('countdowns', [
                'user' => $user,
                'usernames' => $usernames,
                'countdowns' => $formattedCountdowns
            ]);
        } catch (Exception $e) {
            $this->logger->error("Error while loading countdowns: " . $e->getMessage());

            SessionHelper::set('error', 'Nie udało się załadować stronę odliczania.');

            header("Location: /login");
            exit;
        }
    }


    public function announcements(): void
    {
        try {
            $user = $this->getActiveUser();
            $users = $this->userService->getAll();
            $announcements = $this->announcementService->getAll();

            $usernames = [];
            foreach ($users as $u) {
                $usernames[$u->id] = $u->username;
            }

            foreach ($announcements as $announcement) {
                $formattedAnnouncements[] = (object) [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'text' => $announcement->text,
                    'userId' => $announcement->userId,
                    'date' => $announcement->date instanceof \DateTimeImmutable 
                        ? $announcement->date->format('Y-m-d') 
                        : $announcement->date,
                    'validUntil' => $announcement->validUntil instanceof \DateTimeImmutable 
                        ? $announcement->validUntil->format('Y-m-d') 
                        : $announcement->validUntil
                ];
            }

            $this->render('announcements', [
                'user' => $user,
                'usernames' => $usernames,
                'announcements' => $formattedAnnouncements
            ]);
        } catch (Exception $e) {
            $this->logger->error("An error occurred: ".$e->getMessage());
            $this->errorController->internalServerError();
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
                $this->logger->debug("User verification request received.");
                $this->checkCsrf();

                $username = trim($_POST['username']);
                $password = trim($_POST['password']);

                if (empty($password) || empty($username)) {
                    $this->logger->error("Password or username cannot be null!");
                    SessionHelper::set("error", "Pola nazwy użytkownika i hasła muszą być wypełnione!");
                    header("Location: /login");
                    exit;
                }

                $user = $this->userService->getByExactUsername($username);

                if ($user && password_verify($password, $user->passwordHash)) {
                    $this->logger->debug("Correct password for given username.");
                    $this->setCsrf();
                    SessionHelper::set('user_id', $user->id);
                    header("Location: /panel");
                } else {
                    $this->logger->debug("Incorrect password for given username!");
                    SessionHelper::set('error', 'Nieprawidłowa nazwa użytkownika lub hasło!');
                    header("Location: /login");
                }
                exit;
            } catch (Exception $e) {
                $this->logger->error("Authentication error occurred: ".$e->getMessage());
                $this->errorController->internalServerError();
                header("Location: /login");
                exit;
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
                SessionHelper::set('error', 'Nieprawidłowe ID ogłoszenia.');
                header('Location: /panel/announcements');
                exit;
            }

            try {
                $this->logger->debug("Announcement deletion request received", ['id' => $announcementId]);

                $result = $this->announcementService->delete($announcementId);

                if ($result) {
                    $this->logger->debug("Announcement deleted", ['id' => $announcementId]);
                    SessionHelper::set('success', 'Ogłoszenie zostało usunięte.');
                } else {
                    $this->logger->error("Announcement could not be deleted", ['id' => $announcementId]);
                    SessionHelper::set('error', 'Nie udało się usunąć ogłoszenia.');
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                    SessionHelper::set('success', 'Udało się dodać ogłoszenie.');
                    $this->logger->info("Announcement added successfully", ['user_id' => $userId]);
                } else {
                    SessionHelper::set('error', 'Nie udało się dodać ogłoszenia.');
                    $this->logger->warning("Announcement add failed", ['user_id' => $userId]);
                }

            } catch (Exception $e) {
                SessionHelper::set('error', 'Wystąpił błąd podczas dodawania ogłoszenia.');
                $this->logger->error("Exception while adding announcement", ['error' => $e->getMessage()]);
            }

            header('Location: /panel/announcements');
            exit;
        }
    }

    public function editAnnouncement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $id = (int)($_POST['announcement_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $text = trim($_POST['text'] ?? '');
            $validUntil = $_POST['valid_until'] ?? '';

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
    }

    public function addUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $username = trim($_POST['username']);
            $password = trim($_POST['password']);

            try {
                $this->logger->debug("add_user request received");

                if (!isset($username) || !isset($password)) {
                    $this->logger->error("Username and password are required");
                    SessionHelper::set('error', 'Username and password are required!');
                    header('Location: /panel/users');
                    exit;
                }

                $data = [
                    'username' => $username,
                    'password' => $password
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
                $errorMessage = match($e->getMessage()) {
                    'Username too long' => 'Nazwa użytkownika jest za długa.',
                    'Password hash too short' => 'Hasło jest za krótkie.',
                    "User can't delete themselves." => "Użytkownik nie może usunąć sam siebie.",
                    default => 'Nie udało się dodać użytkownika.'
                };
                SessionHelper::set('error', $errorMessage);
                header('Location: /panel/users');
                exit;
            }
        }
    }

    public function deleteUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $userToDelete = trim($_POST['user_id']);

            $user = $this->getActiveUser();
            $userId = $user->id;
            try {
                if ($userId === $userToDelete) {
                    SessionHelper::set('error', 'Użytkownik nie może usunąć sam siebie!');
                    header('Location: /panel/users');
                    exit;
                }

                $result = $this->userService->delete($userId, $userToDelete);
                if ($result) {
                    $this->logger->info("User deleted successfully");
                } else {
                    $this->logger->error("User deletion failed");
                    SessionHelper::set('error', 'Nie udało się usunąć użytkownika');
                }
                header('Location: /panel/users');
                exit;
            } catch (Exception $e) {
                $this->logger->error('User deletion failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Nie udało usunąć się użytkownika');
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

                $this->logger->debug("add_countdown request received", [
                    'user_id' => $userId,
                    'title' => $title
                ]);

                if (empty($title) || empty($count_to)) {
                    SessionHelper::set('error', 'All fields must be filled.');
                    header('Location: /panel/countdowns');
                    exit;
                }

                $data = [
                    'title' => $title,
                    'count_to' => $count_to
                ];

                $this->countdownService->create($data, $userId);
                $this->logger->info("Countdown added successfully");
                header('Location: /panel/countdowns');
                exit;
            } catch (Exception $e) {
                $this->logger->error('Countdown adding failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Nie udało się dodać odliczania.');
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

    public function editCountdown(): void 
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $this->checkIsUserLoggedIn();

            $newCountdownTitle = trim($_POST['title']);
            $newRawCountdownCountTo = $_POST['count_to'];
            $countdownId = (int)$_POST['countdown_id'];

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

                if ($newCountdownTitle !== $countdown->title) {
                    $updates['title'] = $newCountdownTitle;
                }

                if ($newCountdownCountTo !== $countdown->countTo) {
                    $updates['count_to'] = $newCountdownCountTo;
                }

                if (!empty($updates)) {
                    $this->countdownService->update($countdownId, $updates);
                    $this->logger->debug("Updated countdown fields", ['countdown_id' => $countdownId, 'updates' => $updates]);
                }

                $this->logger->debug("Countdown updated successfully");
                header('Location: /panel/countdowns');
                exit;
            } catch (Exception $e) {
                $this->logger->error('Countdown update failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Nie udało się edytować odliczania.');
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

                $this->moduleService->toggle($moduleId);
                $action = $enable ? 'włączony' : 'wyłączony';
                $this->logger->debug("Moduł $moduleId został $action");
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
                $module = $this->moduleService->getById($moduleId);
    
                if (empty($module)) {
                    throw new Exception("Module not found");
                }
    
                $updates = [];
    
                // Formatuj daty do porównania
                if ($newModuleStartTime !== '' && $newModuleStartTime !== $module->startTime->format('H:i:s')) {
                    $updates['start_time'] = $newModuleStartTime;
                }
    
                if ($newModuleEndTime !== '' && $newModuleEndTime !== $module->endTime->format('H:i:s')) {
                    $updates['end_time'] = $newModuleEndTime;
                }
    
                if ($newModuleIsActive !== (int)$module->isActive) {
                    $this->moduleService->toggle($moduleId);
                }
    
                if (!empty($updates)) {
                    $this->moduleService->update($moduleId, $updates);
                }
    
                $this->logger->debug("Module updated successfully");
                header('Location: /panel/modules');
                exit;
            } catch (Exception $e) {
                $this->logger->error('Module edit failed', ['error' => $e->getMessage()]);
                SessionHelper::set('error', 'Nie udało się edytować modułu');
                header('Location: /panel/modules');
                exit;
            }
        }
    }
    
}