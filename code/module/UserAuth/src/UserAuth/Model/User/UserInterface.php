<?php

namespace UserAuth\Model\User;

use UserAuth\Model\JWT;
use UserAuth\Model\User\Storage\StorageInterface;
use UserAuth\Model\User\Authenticator\AuthenticatorInterface;

interface UserInterface
{
    /**
    * Authenticate user with an ID (usually email) and might require a password depending on the authenticator
    *
    * @param string $id, the var is the unique identifyer of the user (i.e. email, userId, etc.)
    * @return bool, true if successful false (or throws exception) otherwise
    */
    public function authenticate(...$args): bool|array;
    /**
    * Synonym of authenticate() function
    *
    * @param string $id, the var is the unique identifyer of the user (i.e. email, userId, etc.)
    * @return bool, true if successful false (or throws exception) otherwise
    */
    public function login(...$args): bool|array;

    /**
    * Log the user out and destroy the session
    *
    * @return UserInterface
    */
    public function logout(): self;

    /**
    * Return true if logged in, false otherwise. By default
    *
    * @return bool, return true if logged in, false otherwise.
    */
    public function isLoggedIn(): bool;

    /**
    * Returns the ID field defined in the const ID_FIELD of the class
    *
    * @return mixed value of self::ID_FIELD
    */
    public function getUserId(): ?string;

    /**
    * Get the content of the Javascript Web Token (when using API)
    *
    * @param string $jwt
    * @return array containing the content of the JWT
    * @throws \UserAuth\Exception\JwtException If the token is null or invalid
    * @throws \UserAuth\Exception\JwtExpiredException If the token is expired
    */
    public function jwtToData(string $jwt): array;

    /**
    * Generate and return the Javascript Web Token (when using API)
    *
    * @param int $time How long the token should be valid for in seconds (86400=24hrs)
    */
    public function getJWT(int $time = 86400): string;

    /**
    * Get the data from the user that should be saved in the token. Remember that
    * token data is public, the user can see it. Do not put some private or
    * protected data in JWT!!
    *
    * @param int $time, in case the length of time the token will live change the data...
    *
    * @return array with the data to put in the JWT
    */
    public function getDataForJWT(int $time = 86400): array;

    /**
    * Set the JWT object (should be set in the factory)
    *
    * @param \UserAuth\Model\JWT $obj
    * @return \UserAuth\Model\User\UserInterface
    */
    public function setJwtObj(JWT $jwt): self;

    /**
     * Set the storage for your user (MySQL, Mongo, File, LDAP, etc.)
     * @param \UserAuth\Model\User\Storage\StorageInterface $storage
     * @return \UserAuth\Model\User\UserInterface
     */
    public function setStorage(StorageInterface $storage): self;

    /**
     * Set the authenticator for your user (credentials, email, token, etc.)
     * @param \UserAuth\Model\User\Authenticator\AuthenticatorInterface $authenticator
     * @return \UserAuth\Model\User\UserInterface
     */
    public function setAuthenticator(AuthenticatorInterface $authenticator): self;

    /**
     * Set the name of the ID field for your user (ex: email, userId, accountname, etc.)
     * @param string $idField
     * @return \UserAuth\Model\User\UserInterface
     */
    public function setIdField(string $idField): self;
}
