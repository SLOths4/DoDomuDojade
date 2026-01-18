<?php
declare(strict_types=1);

namespace App\Infrastructure\Twig;

use DateTimeImmutable;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Throwable;

final class TwigFactory
{
    /**
     * Relatywnie do root projektu
     */
    private const string TEMPLATES_DIR = 'src/Presentation/View/templates';
    private const string COMPONENTS_DIR = '@components';

    public static function create(
        string $rootPath,
        string $cachePath,
        bool $debug
    ): Environment {
        try {
            $viewsPath = $rootPath . '/' . self::TEMPLATES_DIR;

            if (!is_dir($viewsPath)) {
                throw TwigException::invalidViewsPath($viewsPath);
            }

            $loader = new FilesystemLoader($viewsPath, $rootPath);
            $loader->addPath($viewsPath . '/components', self::COMPONENTS_DIR);

            $twig = new Environment($loader, [
                'cache' => $debug ? false : $cachePath,
                'debug' => $debug,
                'auto_reload' => $debug,
                'charset' => 'UTF-8',
                'strict_variables' => $debug,
                'autoescape' => 'html',
            ]);

            if ($debug) {
                $twig->addExtension(new DebugExtension());
            }

            $twig->addFilter(new TwigFilter(
                'formatDate',
                fn($date) => $date instanceof DateTimeImmutable
                    ? $date->format('d.m.Y H:i')
                    : $date
            ));

            $twig->addGlobal('siteName', 'DoDomuDojadę');

            return $twig;

        } catch (Throwable $e) {
            throw TwigException::initializationFailed($viewsPath ?? 'unknown', $e);
        }
    }
}
