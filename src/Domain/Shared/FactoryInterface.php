<?php

namespace App\Domain\Shared;

interface FactoryInterface
{
    public static function create(...$params): object;
}