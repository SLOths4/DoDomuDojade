<?php

namespace src\core;

class Controller{
    protected function render($view, $data = []): void
    {
        extract($data);
        $file = __DIR__ . "/../views/$view.php";
        if (file_exists($file)) {
            include $file;
        } else {
            echo "Plik widoku nie został znaleziony: $file";
        }
    }
}
