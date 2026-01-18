<?php

namespace App\Domain\Module;

use Exception;

interface ModuleRepositoryInterface
{
    /**
     * Finds a module by its ID.
     *
     * @param int $id The module ID
     * @return Module|null The module entity or null if not found
     * @throws Exception
     */
    public function findById(int $id): ?Module;

    /**
     * Finds a module by its name.
     *
     * @param ModuleName $moduleName The module name value object
     * @return Module|null The module entity or null if not found
     * @throws Exception
     */
    public function findByName(ModuleName $moduleName): ?Module;

    /**
     * Retrieves all modules from storage.
     *
     * @return Module[] Array of all module entities
     * @throws Exception
     */
    public function findAll(): array;

    /**
     * Updates an existing module entity.
     *
     * @param Module $module The module entity with updated data
     * @return bool True if update was successful, false otherwise
     * @throws Exception
     */
    public function update(Module $module): bool;
}
