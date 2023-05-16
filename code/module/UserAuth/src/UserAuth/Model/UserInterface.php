<?php
namespace UserAuth\Model;

use \GcNotify\GcNotify;

interface UserInterface
{
    /**
    * Synonym of authenticate() function
    *
    * @param String $email, the var is called email but this is the unique identifyer of the user
    * @param String $password
    * @return bool, true if successful false (or throws exception) otherwise
    */
    public function login(String $username, String $password) : bool;

    /**
    * Authenticate user with an ID (usually email) and password
    *
    * @param String $email, the var is called email but this is the unique identifyer of the user
    * @param String $password
    * @return bool, true if successful false (or throws exception) otherwise
    */
    public function authenticate(String $username, String $password) : bool;
    /**
    * Log the user out and destroy the session
    *
    * @return UserInterface
    */
    public function logout() : self;

    /**
    * Return true if logged in, false otherwise. By default
    *
    * @return bool, return true if logged in, false otherwise.
    */
    public function isLoggedIn() : bool;

    /**
    * Returns the ID field defined in the const ID_FIELD of the class
    *
    * @return mixed value of self::ID_FIELD
    */
    public function getUserId();

    /**
    * Get the content of the Javascript Web Token (when using API)
    *
    * @param String $jwt
    * @return array containing the content of the JWT
    * @throws UserAuth\Exception\JwtException If the token is null or invalid
    * @throws UserAuth\Exception\JwtExpiredException If the token is expired
    */
    public function jwtToData(String $jwt);

    /**
    * Generate and return the Javascript Web Token (when using API)
    *
    * @param mixed $time How long the token should be valid for in seconds (86400=24hrs)
    */
    public function getJWT(Int $time = 86400);
}
