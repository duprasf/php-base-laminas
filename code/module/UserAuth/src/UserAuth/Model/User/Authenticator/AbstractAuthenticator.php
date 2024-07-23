<?php

namespace UserAuth\Model\User\Authenticator;

use UserAuth\Model\User\Storage\StorageInterface;
use UserAuth\Model\User\Authenticator\AuthenticatorInterface;

abstract class AbstractAuthenticator implements AuthenticatorInterface
{
    /**
     * Use for Load from JWT or load from session
     * @param string $idValue
     * @return array
     */
    public function directLogin(string $idValue): bool|array
    {
        return $this->getStorage()->read($idValue);
    }

    private $storage;
    /**
     * Set the storage for your user (MySQL, Mongo, File, LDAP, etc.)
     * @param \UserAuth\Model\User\Storage\StorageInterface $storage
     * @return \UserAuth\Model\User\UserInterface
     */
    public function setStorage(StorageInterface $storage): self
    {
        $this->storage = $storage;
        return $this;
    }
    protected function getStorage(): StorageInterface
    {
        return $this->storage;
    }

    /**
     * Used by the User to determine if it should pass the storage obj or if it is already there
     * @return bool
     */
    public function hasStorage(): bool
    {
        return $this->storage instanceof StorageInterface;
    }

    private $canRegister = true;
    public function setCanRegister(bool $canRegister): self
    {
        $this->canRegister = $canRegister;
        return $this;
    }
    protected function getCanRegister(): bool
    {
        return $this->canRegister;
    }

    private $idField;
    public function setIdField(string $idField): self
    {
        $this->idField = $idField;
        return $this;
    }
    protected function getIdField(): string
    {
        return $this->idField;
    }
}
