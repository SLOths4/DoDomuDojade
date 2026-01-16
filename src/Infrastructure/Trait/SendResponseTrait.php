<?php

namespace App\Infrastructure\Trait;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

trait SendResponseTrait {
    protected function sendSuccess(array $data, string $message = '', int $responseCode = 200): ResponseInterface
    {
        $response = new Response($responseCode, ['Content-Type' => 'application/json']);
        $response->getBody()->write(json_encode(
            [
                'status' => 'success',
                'data' => $data,
                'message' => $message
            ]
        ));

        return $response;
    }

    protected function sendError(string $message, int $code, array $data, int $responseCode = 200): ResponseInterface
    {
        $response = new Response($responseCode, ['Content-Type' => 'application/json']);
        $response->getBody()->write(json_encode(
            [
                'status' => 'error',
                'message' => $message,
                'code' => $code,
                'data' => $data
            ]
        ));

        return $response;
    }
}