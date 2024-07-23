<?php

namespace UserAuth\Model\User\Authenticator;

use UserAuth\Model\User\Storage\StorageInterface;

interface AuthenticatorInterface
{
    /**
     * used by the User to determine if it should pass the storage obj or if it is already there
     * @return bool
     */
    public function hasStorage(): bool;

    /**
     * Each authenticator (Email, Credentials, token, etc.) will require different parameters to register a new user
     * @return bool
     */
    public function register(array $data): bool;

    /**
     * Each authenticator (Email, Credentials, token, etc.) will require different parameters to authenticate a user
     * @return array|bool
     */
    public function authenticate(): array|bool;

    /**
     * Use for Load from JWT or load from session
     * @param string $idValue
     * @return bool|array
     */
    public function directLogin(string $idValue): bool|array;

    /**
     * Logout the user, in case something needs to be done in the storage
     * @return bool
     */
    public function logout(): bool;

    /**
     * Set the storage for your user (MySQL, Mongo, File, LDAP, etc.)
     * @param \UserAuth\Model\User\Storage\StorageInterface $storage
     * @return \UserAuth\Model\User\UserInterface
     */
    public function setStorage(StorageInterface $storage): self;

    public function setCanRegister(bool $canRegister): self;

    public function setIdField(string $idField): self;
}
