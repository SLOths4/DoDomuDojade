<?php

namespace src\service;

use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use src\entities\User;
use src\repository\UserRepository;

readonly class UserService
{
    private array $ALLOWED_FIELDS;
    public function __construct(
        private UserRepository  $repo,
        private int             $MAX_USERNAME_LENGTH,
        private int             $MIN_PASSWORD_LENGTH,
        private LoggerInterface $logger,
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
        $this->logger->info('Creating user', [
            'payload_keys' => array_keys($data),
        ]);

        $this->validateNewUser($data);
        if ($this->getByExactUsername($data['username'])) {
            $this->logger->warning('User already exists', [
                'username' => $data['username'],
            ]);
            throw new Exception("User already exists");
        }

        $username = trim($data['username']);
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        $u = new User(
            null,
            $username,
            $passwordHash,
            new DateTimeImmutable()
        );

        $result = $this->repo->add($u);

        $this->logger->info('User creation finished', [
            'username' => $username,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * Updates existing user
     * @param int $id
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function update(int $id, array $data): bool {
        $this->logger->info('Updating user', [
            'user_id' => $id,
            'payload_keys' => array_keys($data),
        ]);

        $this->partialValidate($data);
        $u = $this->repo->findById($id);

        foreach ($this->ALLOWED_FIELDS as $f) {
            if (isset($data[$f])) {
                $this->logger->debug('Updating user field', [
                    'user_id' => $id,
                    'field' => $f,
                ]);
                $u->$f = trim($data[$f]);
            }
        }

        $result = $this->repo->update($u);

        $this->logger->info('User update finished', [
            'user_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * Deletes a user
     * @param int $activeUserId
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delete(int $activeUserId, int $id): bool {
        $this->logger->info('Deleting user', [
            'active_user_id' => $activeUserId,
            'target_user_id' => $id,
        ]);

        $this->repo->findById($id);
        if ($activeUserId === $id) {
            $this->logger->warning("User attempted to delete themselves", [
                'user_id' => $id,
            ]);
            throw new Exception("User can't delete themselves.");
        }

        $result = $this->repo->delete($id);

        $this->logger->info('User delete finished', [
            'target_user_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * Finds all users
     * @return User[]
     * @throws Exception
     */
    public function getAll(): array {
        $this->logger->debug('Fetching all users');

        $users = $this->repo->findAll();

        $this->logger->debug('Fetched all users', [
            'count' => count($users),
        ]);

        return $users;
    }

    /**
     * Finds user by ID
     * @param int $id
     * @return User
     * @throws Exception
     */
    public function getById(int $id): User {
        $this->logger->debug('Fetching user by id', [
            'user_id' => $id,
        ]);

        $user = $this->repo->findById($id);

        $this->logger->debug('Fetched user by id', [
            'user_id' => $id,
        ]);

        return $user;
    }

    /**
     * Finds users by partial username
     * @param string $username
     * @return User[]
     * @throws Exception
     */
    public function getByUsername(string $username): array {
        $this->logger->debug('Fetching users by username (partial match)', [
            'username' => $username,
        ]);

        $users = $this->repo->findByUsername($username);

        $this->logger->debug('Fetched users by username (partial match)', [
            'username' => $username,
            'count' => count($users),
        ]);

        return $users;
    }

    /**
     * Finds a single user by exact username
     * @param string $username
     * @return User|null
     * @throws Exception
     */
    public function getByExactUsername(string $username): ?User {
        $this->logger->debug('Fetching user by exact username', [
            'username' => $username,
        ]);

        $user = $this->repo->findByExactUsername($username);

        $this->logger->debug('Fetched user by exact username', [
            'username' => $username,
            'found' => $user !== null,
        ]);

        return $user;
    }

    /**
     * Changes a user's password
     * @param int $id
     * @param string $newPassword
     * @return bool
     * @throws Exception
     */
    public function changePassword(int $id, string $newPassword): bool {
        $this->logger->info('Changing user password', [
            'user_id' => $id,
        ]);

        if (strlen($newPassword) < $this->MIN_PASSWORD_LENGTH) {
            $this->logger->warning('New password is too short', [
                'user_id' => $id,
                'provided_length' => strlen($newPassword),
                'min_length' => $this->MIN_PASSWORD_LENGTH,
            ]);
            throw new Exception("Password too short");
        }

        $result = $this->repo->updatePassword($id, $newPassword);

        $this->logger->info('Password change finished', [
            'user_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * Validation logic for user creation
     * @param array $d
     * @return void
     * @throws Exception
     */
    private function validateNewUser(array $d): void {
        $this->logger->debug('Validating new user data', [
            'has_username' => array_key_exists('username', $d),
            'has_password' => array_key_exists('password', $d),
        ]);

        if (!isset($d['username']) || !isset($d['password'])) {
            $this->logger->warning('Missing required fields for new user');
            throw new Exception('Missing required fields');
        }
        if (strlen($d['username']) > $this->MAX_USERNAME_LENGTH) {
            $this->logger->warning('Username is too long during user creation', [
                'length' => strlen($d['username']),
                'max_length' => $this->MAX_USERNAME_LENGTH,
            ]);
            throw new Exception('Username too long');
        }
        if (strlen($d['password']) < $this->MIN_PASSWORD_LENGTH) {
            $this->logger->warning('Password is too short during user creation', [
                'length' => strlen($d['password']),
                'min_length' => $this->MIN_PASSWORD_LENGTH,
            ]);
            throw new Exception('Password hash too short');
        }

        $this->logger->debug('New user data validation passed');
    }

    /**
     * Validation logic for user update (partial)
     * @param array $d
     * @return void
     * @throws Exception
     */
    private function partialValidate(array $d): void {
        $this->logger->debug('Validating partial user data', [
            'has_username' => array_key_exists('username', $d),
            'has_password_hash' => array_key_exists('password_hash', $d),
        ]);

        if (isset($d['username']) && strlen($d['username']) > $this->MAX_USERNAME_LENGTH) {
            $this->logger->warning('Username is too long during user update', [
                'length' => strlen($d['username']),
                'max_length' => $this->MAX_USERNAME_LENGTH,
            ]);
            throw new Exception('Username too long');
        }
        if (isset($d['password_hash']) && strlen($d['password_hash']) < $this->MIN_PASSWORD_LENGTH) {
            $this->logger->warning('Password hash is too short during user update', [
                'length' => strlen($d['password_hash']),
                'min_length' => $this->MIN_PASSWORD_LENGTH,
            ]);
            throw new Exception('Password hash too short');
        }

        $this->logger->debug('Partial user data validation passed');
    }
}