<?php
namespace App\Infrastructure\Twig;

use App\Infrastructure\Shared\InfrastructureException;
use Throwable;

final class TwigException extends InfrastructureException
{
    public static function initializationFailed(string $viewsPath, Throwable $previous): self
    {
        return new self(
            sprintf('Failed to initialize Twig with views path: %s', $viewsPath),
            'TWIG_INITIALIZATION_FAILED',
            500,
            $previous
        );
    }

    public static function invalidViewsPath(string $viewsPath): self
    {
        return new self(
            sprintf('Invalid or missing views path: %s', $viewsPath),
            'TWIG_INVALID_PATH',
            500
        );
    }

    public static function renderingFailed(string $template, Throwable $previous): self
    {
        return new self(
            sprintf('Failed to render template: %s', $template),
            'TEMPLATE_RENDERING_FAILED',
            500,
            $previous
        );
    }

    public static function templateNotFound(string $template): self
    {
        return new self(
            sprintf('Template not found: %s', $template),
            'TEMPLATE_NOT_FOUND',
            500
        );
    }
}
