<?php

namespace src\service;

use DateTimeImmutable;
use Exception;
use src\entities\User;
use src\infrastructure\helpers\SessionHelper;
use src\repository\UserRepository;

readonly class UserService {
    private array $ALLOWED_FIELDS;
    public function __construct(
        private UserRepository $repo,
        private int            $MAX_USERNAME_LENGTH,
        private int            $MIN_PASSWORD_LENGTH,
    ) {
        $this->ALLOWED_FIELDS = $this->repo->getAllowedFields();
    }

    /**
     * Adds validated user
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function create(array $data): bool
    {
        $this->validate($data);
        if ($this->getByExactUsername($data['username'])) {
            throw new Exception("User already exists");
        }

        $username = trim($data['username']);
        $passwordHash = password_hash($data['passwordHash'], PASSWORD_DEFAULT);

        $u = new User(
            null,
            $username,
            $passwordHash,
            new DateTimeImmutable()
        );
        return $this->repo->add($u);
    }

    /**
     * Updates existing user
     * @param int $id
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function update(int $id, array $data): bool {
        $this->partialValidate($data);
        $u = $this->repo->findById($id);
        foreach ($this->ALLOWED_FIELDS as $f)
            if (isset($data[$f])) $u->$f = trim($data[$f]);
        return $this->repo->update($u);
    }

    /**
     * Deletes a user
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delete(int $id): bool {
        $userId = SessionHelper::get('user')->id;
        if ($userId === $id) {
            throw new Exception("User can't delete themselves.");
        }
        return $this->repo->delete($id);
    }

    /**
     * Finds all users
     * @return User[]
     * @throws Exception
     */
    public function getAll(): array {
        return $this->repo->findAll();
    }

    /**
     * Finds user by ID
     * @param int $id
     * @return User
     * @throws Exception
     */
    public function getById(int $id): User {
        return $this->repo->findById($id);
    }

    /**
     * Finds users by partial username
     * @param string $username
     * @return User[]
     * @throws Exception
     */
    public function getByUsername(string $username): array {
        return $this->repo->findByUsername($username);
    }

    /**
     * Finds a single user by exact username
     * @param string $username
     * @return User|null
     * @throws Exception
     */
    public function getByExactUsername(string $username): ?User {
        return $this->repo->findByExactUsername($username);
    }

    /**
     * Changes a user's password
     * @param int $id
     * @param string $newHash
     * @return bool
     * @throws Exception
     */
    public function changePassword(int $id, string $newHash): bool {
        if (strlen($newHash) < $this->MIN_PASSWORD_LENGTH)
            throw new Exception("Password too short");
        return $this->repo->updatePassword($id, $newHash);
    }

    /**
     * Validation logic for user creation
     * @param array $d
     * @return void
     * @throws Exception
     */
    private function validate(array $d): void {
        if (strlen($d['username'] ?? '') > $this->MAX_USERNAME_LENGTH)
            throw new Exception('Username too long');
        if (strlen($d['password_hash'] ?? '') < $this->MIN_PASSWORD_LENGTH)
            throw new Exception('Password hash too short');
    }

    /**
     * Validation logic for user update (partial)
     * @param array $d
     * @return void
     * @throws Exception
     */
    private function partialValidate(array $d): void {
        if (isset($d['username']) && strlen($d['username']) > $this->MAX_USERNAME_LENGTH)
            throw new Exception('Username too long');
        if (isset($d['password_hash']) && strlen($d['password_hash']) < $this->MIN_PASSWORD_LENGTH)
            throw new Exception('Password hash too short');
    }
}
