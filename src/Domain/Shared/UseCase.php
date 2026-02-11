<?php

namespace App\Domain\Shared;

use Exception;

/**
 * Describes a use case
 */
abstract class UseCase
{
    /**
     * Execute the use case
     * @param object $dto
     * @throws Exception
     */
    abstract public function execute(object $dto);
}