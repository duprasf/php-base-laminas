<?php

namespace UserAuth\Model\User\Authenticator;

use InvalidArgumentException;
use UserAuth\Exception\UserException;
use UserAuth\Exception\WrongPasswordException;
use UserAuth\Exception\InvalidCredentialsException;
use UserAuth\Model\User\Storage\StorageInterface;
use UserAuth\Model\User\Storage\LdapStorage;

class LdapAuthenticator extends CredentialsAuthenticator
{
    /**
     * Each authenticator (Email, Credentials, token, etc.) will require different parameter to register a new user
     * @throws UserException
     * @return bool
     */
    public function register(array $data): bool
    {
        throw new UserException("Cannot register new user using LDAP at this time");
    }

    /**
     * Each authenticator (Email, Credentials, token, etc.) will require different parameters to authenticate a user
     * @return bool
     */
    public function authenticate(string|null $email = null, string|null $password = null): array|bool
    {
        $data = $this->getStorage()->read($email);
        if(!$data) {
            throw new InvalidCredentialsException();
        }
        if(!$this->getStorage()->validateCredentials($data['dn'], $password)) {
            throw new WrongPasswordException();
        }
        return $data;
    }

    public function setStorage(StorageInterface $storage): self
    {
        if(!$storage instanceof LdapStorage) {
            throw new InvalidArgumentException('LdapAuthenticator can only work with a LdapStorage');
        }
        return parent::setStorage($storage);
    }
}
