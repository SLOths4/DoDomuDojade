<?php

namespace App\Infrastructure\Helper;

final class StaticFileHandlingHelper
{
    private const array ALLOWED_EXTENSIONS = ['css', 'js', 'png', 'jpg', 'jpeg', 'ico', 'svg'];

    private array $mimeMap = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'ico'  => 'image/x-icon',
        'svg'  => 'image/svg+xml',
    ];

    public function __construct(
        private readonly string $publicRoot
    ) {}

    /**
     * @param string $uriPath
     * @return bool
     */
    public function serve(string $uriPath): bool
    {
        $uriPath = rawurldecode(parse_url($uriPath, PHP_URL_PATH) ?: '/');

        if ($uriPath === '/') {
            return false;
        }

        $extension = strtolower(pathinfo($uriPath, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return false;
        }

        $realPublicRoot = realpath($this->publicRoot);
        $fullPath = realpath($this->publicRoot . $uriPath);

        if ($realPublicRoot === false || $fullPath === false) {
            return false;
        }

        if (!str_starts_with($fullPath, $realPublicRoot)) {
            return false;
        }

        if (!is_file($fullPath)) {
            return false;
        }

        $mimeType = $this->mimeMap[$extension] ?? 'application/octet-stream';
        header("Content-Type: $mimeType");
        header('Content-Length: ' . filesize($fullPath));

        header('Cache-Control: public, max-age=86400');

        readfile($fullPath);
        return true;
    }
}