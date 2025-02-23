<?php

namespace src\controllers;

use Exception;
use Monolog\Logger;
use src\core\Controller;
use src\models\UserModel;

/**
 * User controller
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 */
class UserController extends Controller
{
    private UserModel $userModel;
    private Logger $logger;

    function __construct()
    {
        $this->logger = self::initLogger();
        $this->userModel = new UserModel();
    }

    /**
     * @throws Exception
     */
    public function login(): void
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $username = $_POST['username'];
            $password = $_POST['password'];

            $user = $this->userModel->getUserByUsername($username);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user['username'];
                header("Location: /home");
            } else {
                $this->logger->error("Invalid username or password");
            }
        } else {
            require 'src/views/login.php';
        }
    }

}