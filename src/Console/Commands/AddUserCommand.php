<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\User\CreateUserDTO;
use App\Application\User\UseCase\CreateUserUseCase;
use App\Application\Word\FetchWordUseCase;
use App\Console\Command;
use App\Console\ConsoleOutput;
use Exception;

/**
 * Adds new user with provided data
 */
final readonly class AddUserCommand implements Command
{
    /**
     * @param CreateUserUseCase $useCase
     */
    public function __construct(
        private CreateUserUseCase $useCase
    ) {}

    /**
     * @inheritDoc
     */
    public function execute(array $arguments, ConsoleOutput $output): void
    {
        $output->info("Creating new user with username $arguments[0] ...");
        try {
            if (sizeof($arguments) != 2) {
                throw new Exception("Provide username and password");
            }
            $dto = new CreateUserDTO($arguments[0], $arguments[1]);
            $this->useCase->execute($dto);
            $output->success("User added successfully!");
        } catch (Exception $e) {
            $output->error("Failed to add user: " . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'user:add';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Adds a new user';
    }

    /**
     * @inheritDoc
     */
    public function getArgumentsCount(): int
    {
        return 2;
    }
}
