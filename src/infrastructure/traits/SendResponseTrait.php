<?php

namespace src\infrastructure\traits;

use JetBrains\PhpStorm\NoReturn;

trait SendResponseTrait {
    #[NoReturn]
    protected function sendSuccess(array $data, string $message = ''): void
    {
        header('Content-type: application/json');
        echo json_encode(['status' => 'success', 'data' => $data, 'message' => $message]);
        exit;
    }

    #[NoReturn]
    protected function sendError(string $message, int $code, array $data): void
    {
        header('Content-type: application/json');
        echo json_encode(
            [
                'status' => 'error',
                'message' => $message,
                'code' => $code,
                'data' => $data
            ]);
        exit;
    }
}