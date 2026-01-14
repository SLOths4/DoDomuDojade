<?php

declare(strict_types=1);

namespace App\Infrastructure\Factory;

use App\config\Config;
use App\Domain\Shared\FactoryInterface;
use DateTimeImmutable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

/**
 * Twig factory
 */
final class TwigFactory
{
    /**
     * Creates Twig environment
     * @param string $viewsPath
     * @param string $cachePath
     * @param bool $debug
     * @return Environment
     * @throws LoaderError
     */
    public static function create(string $viewsPath, string $cachePath, bool $debug): Environment
    {

        $loader = new FilesystemLoader($viewsPath, $viewsPath . '/..');
        $loader->addPath($viewsPath . '/components', '@components');

        $twig = new Environment($loader, [
            'cache' => $cachePath,
            'debug' => $debug,
            'auto_reload' => $debug,
            'charset' => 'UTF-8',
            'strict_variables' => $debug,
            'autoescape' => 'html'
        ]);

        if ($debug) {
            $twig->addExtension(new DebugExtension());
        }

        $twig->addFilter(new TwigFilter('formatDate', fn($date) => $date instanceof DateTimeImmutable ? $date->format('d.m.Y H:i') : $date
        ));

        $twig->addGlobal('siteName', 'DoDomuDojadę');

        return $twig;
    }
}
