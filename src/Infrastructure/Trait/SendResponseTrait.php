<?php

namespace App\Infrastructure\Trait;

use JetBrains\PhpStorm\NoReturn;

trait SendResponseTrait {
    #[NoReturn]
    protected function sendSuccess(array $data, string $message = '', int $responseCode = 200): void
    {
        header('Content-type: application/json');
        http_response_code($responseCode);

        echo json_encode(
            [
                'status' => 'success',
                'data' => $data,
                'message' => $message
            ]);
        exit;
    }

    #[NoReturn]
    protected function sendError(string $message, int $code, array $data, int $responseCode = 200): void
    {
        header('Content-type: application/json');
        http_response_code($responseCode);

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