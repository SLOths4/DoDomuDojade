<?php

namespace App\Domain\Countdown;

use Exception;

/**
 * Describes behavior of the countdown repository
 */
interface CountdownRepositoryInterface
{
    /**
     * Finds a countdown by its ID.
     *
     * @param int $id The countdown ID
     * @return Countdown|null The countdown entity or null if not found
     * @throws Exception
     */
    public function findById(int $id): ?Countdown;

    /**
     * Finds the current active countdown (with count_to in the future).
     * Returns the next countdown to occur.
     *
     * @return Countdown|null The current countdown or null if none exists
     * @throws Exception
     */
    public function findCurrent(): ?Countdown;

    /**
     * Retrieves all countdowns from storage.
     *
     * @return Countdown[] Array of all countdown entities
     * @throws Exception
     */
    public function findAll(): array;

    /**
     * Persists a new countdown entity.
     *
     * @param Countdown $countdown The countdown entity to add
     * @return int The ID of the newly inserted countdown
     * @throws Exception
     */
    public function add(Countdown $countdown): int;

    /**
     * Updates an existing countdown entity.
     *
     * @param Countdown $countdown The countdown entity with updated data
     * @return bool True if update was successful, false otherwise
     * @throws Exception
     */
    public function update(Countdown $countdown): bool;

    /**
     * Deletes a countdown by its ID.
     *
     * @param int $id The countdown ID to delete
     * @return bool True if deletion was successful, false otherwise
     * @throws Exception
     */
    public function delete(int $id): bool;

    /**
     * Updates a specific field in a countdown.
     *
     * @param int $id The countdown ID
     * @param string $field The field name to update
     * @param string $value The new value for the field
     * @return bool True if the update was successful, false otherwise
     * @throws Exception
     */
    public function updateField(int $id, string $field, string $value): bool;
}