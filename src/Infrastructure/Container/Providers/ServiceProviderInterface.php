<?php
declare(strict_types=1);

namespace App\Infrastructure\Container\Providers;

use App\Infrastructure\Container;

interface ServiceProviderInterface
{
    public function register(Container $c): void;
}
