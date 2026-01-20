<?php

namespace App\Domain\Shared;

use Exception;

abstract class UseCase
{
    /**
     * Execute the use case
     * @param object $dto
     * @throws Exception
     */
    abstract public function execute(object $dto);
}