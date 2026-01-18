<?php

namespace App\Domain\User;

use Exception;

interface UserRepositoryInterface
{
    /**
     * Finds a user by its ID.
     *
     * @param int $id The user ID
     * @return User The user entity
     * @throws Exception if user not found
     */
    public function findById(int $id): User;

    /**
     * Finds a user by exact username match.
     *
     * @param string $username The username to search for
     * @return User|null The user entity or null if not found
     * @throws Exception
     */
    public function findByExactUsername(string $username): ?User;

    /**
     * Finds a user by partial username match.
     *
     * @param string $username The partial username to search for
     * @return User[] Array of matching user entities
     * @throws Exception
     */
    public function findByUsernamePartial(string $username): array;

    /**
     * Retrieves all users from storage.
     *
     * @return User[] Array of all user entities
     * @throws Exception
     */
    public function findAll(): array;

    /**
     * Persists a new user entity.
     *
     * @param User $user The user entity to add
     * @return bool True if insertion was successful, false otherwise
     * @throws Exception
     */
    public function add(User $user): bool;

    /**
     * Updates an existing user entity.
     *
     * @param User $user The user entity with updated data
     * @return bool True if update was successful, false otherwise
     * @throws Exception
     */
    public function update(User $user): bool;

    /**
     * Deletes a user by its ID.
     *
     * @param int $id The user ID to delete
     * @return bool True if deletion was successful, false otherwise
     * @throws Exception
     */
    public function delete(int $id): bool;

    /**
     * Updates user password hash.
     *
     * @param int $id The user ID
     * @param string $newPasswordHash The new password hash
     * @return bool True if update was successful, false otherwise
     * @throws Exception
     */
    public function updatePassword(int $id, string $newPasswordHash): bool;

    /**
     * Returns allowed fields for update operations.
     * Excludes immutable fields like id and createdAt.
     *
     * @return array Array of allowed field names
     */
    public function getAllowedFields(): array;
}
