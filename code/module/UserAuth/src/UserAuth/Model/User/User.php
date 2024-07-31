<?php

namespace UserAuth\Model\User;

use ArrayObject;
use Laminas\EventManager\EventManagerInterface as EventManager;
use Laminas\Session\Container;
use UserAuth\UserEvent;
use UserAuth\Model\JWT;
use UserAuth\Exception\UserException;
use UserAuth\Exception\JwtException;
use UserAuth\Model\User\Storage\StorageInterface;
use UserAuth\Model\User\Authenticator\AuthenticatorInterface;

class User extends ArrayObject implements UserInterface
{
    private $errors = [];
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function register(array $data)
    {
        $this->getEventManager()->trigger(UserEvent::REGISTER.'.pre', $this, [$this->getIdField() => $data[$this->getIdField()]]);
        $result = $this->getAuthenticator()->register($data);
        $this->getEventManager()->trigger(UserEvent::REGISTER, $this, [$this->getIdField() => $data[$this->getIdField()]]);
        return $result;
    }

    /**
    * Synonym of authenticate() function
    *
    * @param $args, this function can accept any number of elements based on the authenticator of this user class
    * @return bool, true if successful false (or throws exception) otherwise
    */
    public function __invoke(...$args): bool
    {
        return $this->authenticate(...$args);
    }

    /**
    * Authenticate user with an ID (usually email) and might require a password depending on the authenticator
    *
    * @param $args this function can accept any number of elements based on the authenticator of this user class
    * @return bool true if successful false (or throws exception) otherwise
    */
    public function authenticate(...$args): bool|array
    {
        if(isset($args[$this->getIdField()])) {
            $this->getEventManager()->trigger(
                UserEvent::LOGIN.'.pre',
                $this,
                [$this->getIdField() => $args[$this->getIdField()], 'target' => $args[$this->getIdField()]]
            );
        }
        $data = $this->getAuthenticator()->authenticate(...$args);
        if(!$data) {
            $this->getEventManager()->trigger(UserEvent::LOGIN.'.err', $this, [
                $this->getIdField() => $args[$this->getIdField()],
                'target' => $args[$this->getIdField()],
                'error' => $this->getIdField().' not found'
            ]);
            $this->logout();
            return false;
        }
        $this->getEventManager()->trigger(
            UserEvent::LOGIN,
            $this,
            ['target' => reset($args)]
        );
        if(!is_array($data)) {
            return true;
        }
        $this->setArrayAndSession($data);
        return $data;
    }

    protected function setArrayAndSession(array $data): self
    {
        unset($data['password']);
        unset($data["token"]);
        unset($data["expiryTimestamp"]);
        unset($data["currentJWT"]);
        $this->exchangeArray($data);
        $this->saveToSession();

        return $this;
    }

    /**
    * Synonym of authenticate() function
    *
    * @param $args this function can accept any number of elements based on the authenticator of this user class
    * @return bool true if successful false (or throws exception) otherwise
    */
    public function login(...$args): bool|array
    {
        return $this->authenticate(...$args);
    }

    /**
    * Log the user out and destroy the session
    *
    * @return \UserAuth\Model\User\UserInterface
    */
    public function logout(): self
    {
        $id = $this[$this->getIdField()];
        $this->getEventManager()->trigger(UserEvent::LOGOUT.'.pre', $this, [$this->getIdField() => $id, 'target' => $id]);
        $this->getAuthenticator()->logout();
        $this->getEventManager()->trigger(UserEvent::LOGOUT, $this, [$this->getIdField() => $id, 'target' => $id]);
        return $this;
    }

    /**
    * Load a user from the JWT. The expiry time of the JWT should be checked before allowing this.
    *
    * @param string|null $jwt the JavaScript Web Token received from the client
    * @return bool, true if successful false otherwise
    * @throws \UserAuth\Exception\JwtException If the token is null or invalid
    * @throws \UserAuth\Exception\JwtExpiredException If the token is expired
    * @throws \UserAuth\Exception\UserException if the ID field is not set in the JWT
    */
    public function loadFromJwt(?string $jwt): bool|array
    {
        if($jwt == null) {
            throw new JwtException('JWT is null');
        }
        $data = $this->jwtToData($jwt);
        if(!isset($data[$this->getIdField()])) {
            throw new UserException('ID field ('.$this->getIdField().') does not exists in JWT');
        }
        $this->setArrayAndSession($this->getAuthenticator()->directLogin($data[$this->getIdField()]));
        return true;
    }

    /**
    * Return true if logged in, false otherwise.
    *
    * @return bool, return true if logged in, false otherwise.
    */
    public function isLoggedIn(): bool
    {
        return isset($this[$this->getIdField()]) && !!$this[$this->getIdField()];
    }

    /**
    * Returns the ID field defined $this->getIdField()
    *
    * @return mixed value of $this->getIdField()
    */
    public function getUserId(): string
    {
        return $this[$this->getIdField()] ?? null;
    }

    /**
    * Get the content of the Javascript Web Token (when using API)
    *
    * @param string $jwt
    * @return array containing the content of the JWT
    * @throws \UserAuth\Exception\JwtException If the token is null or invalid
    * @throws \UserAuth\Exception\JwtExpiredException If the token is expired
    */
    public function jwtToData(string $jwt): array
    {
        return $this->getJwtObj()->getPayload($jwt);
    }

    /**
    * Generate and return the Javascript Web Token (when using API)
    *
    * @param int $time How long the token should be valid for in seconds (86400=24hrs)
    * @return string a JWT to be sent to the browser or an empty string if not logged in
    */
    public function getJWT(int $time = 86400): string
    {
        if(!$this->isLoggedIn()) {
            return '';
        }
        $jwt = $this->getJwtObj();
        // get the payload from getDataForJWT() which should be overwritten by the child class
        $payload = $this->getDataForJWT($time);
        return $jwt->generate($payload, $time);
    }

    /**
    * Get the data from the user that should be saved in the token. Remember that
    * token data is public, the user can see it. Do not put some private or
    * protected data in JWT!!
    *
    * @param int $time, in case the length of time the token will live change the data...
    * @return array with the data to put in the JWT
    */
    public function getDataForJWT(int $time = 86400): array
    {
        $payload = [
            $this->getIdField() => $this[$this->getIdField()],
        ];
        if(!isset($payload['id'])) {
            $payload['id'] = $this[$this->getIdField()] ?? null;
        }
        return $payload;
    }

    //****************** Session
    protected function saveToSession(): self
    {
        if(!$this->getUseSession()) {
            return $this;
        }
        $container = new Container($this->getSessionName());
        $data = $this->getArrayCopy();
        if(!isset($data['iat'])) {
            $data['iat'] = time();
        }
        $data['exp'] = time() + $this->getSessionLength();
        $container->exchangeArray($data);
        return $this;
    }

    public function loadFromSession(): bool
    {
        if(!$this->getUseSession()) {
            return $this;
        }
        $container = new Container($this->getSessionName());
        $data = $container->getArrayCopy();
        if(!isset($data[$this->getIdField()])) {
            return false;
        }

        if($data['exp'] < time()) {
            $container->exchangeArray([]);
            return false;
        }
        $this->setArrayAndSession($this->getStorage()->read($data[$this->getIdField()]));
        return true;
    }

    protected function destroySession(): self
    {
        $container = new Container($this->getSessionName());
        $container->exchangeArray([]);

        return $this;
    }

    protected function getSessionInfo(): array
    {
        $container = new Container($this->getSessionName());
        return $container->getArrayCopy();
    }

    //***************** Getters and setters
    private $jwtObj;
    /**
    * Set the JWT object (should be set in the factory)
    *
    * @param \UserAuth\Model\JWT $obj
    * @return \UserAuth\Model\User\UserInterface
    */
    public function setJwtObj(JWT $jwt): self
    {
        $this->jwtObj = $jwt;
        return $this;
    }
    protected function getJwtObj(): JWT
    {
        return $this->jwtObj;
    }

    private $useSession = true;
    /**
     * Tell the code if it should use the session or not (default is to use it)
     * @param bool $useSession
     * @return \UserAuth\Model\User\User
     */
    public function setUseSession(bool $useSession): self
    {
        $this->useSession = $useSession;
        return $this;
    }
    protected function getUseSession(): bool
    {
        return $this->useSession;
    }

    private $sessionLength = 3600;
    /**
     * Number of seconds the session will last
     * @param int $sessionLength
     * @return \UserAuth\Model\User\User
     */
    public function setSessionLength(int $sessionLength): self
    {
        $this->sessionLength = $sessionLength;
        return $this;
    }
    protected function getSessionLength(): int
    {
        return $this->sessionLength;
    }

    private $sessionName = 'UserAuth';
    /**
     * Set the name of the session key, if you need to change it
     * @param string $sessionName
     * @return \UserAuth\Model\User\User
     */
    public function setSessionName(string $sessionName): self
    {
        $this->sessionName = $sessionName;
        return $this;
    }
    protected function getSessionName(): string
    {
        return $this->sessionName;
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
        if(! $this->storage instanceof StorageInterface) {
            throw new UserException("No Storage defined");
        }
        $this->storage->setIdField($this->getIdField());
        return $this->storage;
    }

    private $authenticator;
    /**
     * Set the authenticator for your user (credentials, email, token, etc.)
     * @param \UserAuth\Model\User\Authenticator\AuthenticatorInterface $authenticator
     * @return \UserAuth\Model\User\UserInterface
     */
    public function setAuthenticator(AuthenticatorInterface $authenticator): self
    {
        $this->authenticator = $authenticator;
        return $this;
    }
    protected function getAuthenticator(): AuthenticatorInterface
    {
        if(! $this->authenticator instanceof AuthenticatorInterface) {
            throw new UserException("No Authenticator defined");
        }
        if(!$this->authenticator->hasStorage()) {
            $this->authenticator->setStorage($this->getStorage());
        }
        $this->authenticator->setIdField($this->getIdField());
        return $this->authenticator;
    }

    private $idField;
    /**
     * Set the name of the ID field for your user (ex: email, userId, accountname, etc.)
     * @param string $idField
     * @return \UserAuth\Model\User\UserInterface
     */
    public function setIdField(string $idField): self
    {
        $this->idField = $idField;
        return $this;
    }
    protected function getIdField()
    {
        return $this->idField;
    }

    protected $eventManager;
    /**
    * should be used in the factory
    *
    * @param \Laminas\EventManager\EventManagerInterface $manager
    * @return User
    */
    public function setEventManager(EventManager $manager)
    {
        $this->eventManager = $manager;
        return $this;
    }
    /**
    * Get the EventManager to triger for different events
    *
    * Other modules/composents could listen for those to execute code at specific time
    *
    * Standard events:
    * * UserAuth\UserEvent::LOGIN.'.pre': Should be sent before testing the credentials
    * * UserAuth\UserEvent::LOGIN_FAILED: Should be sent if the credentials are wrong, currently it is the same for no user or wrong password but the event will containt the userId if user is found
    * * UserAuth\UserEvent::LOGIN: Should be sent when the user has been logged in
    * * UserAuth\UserEvent::LOGOUT.'.pre': Should be sent before logout, in case operations are required BEFORE logout
    * * UserAuth\UserEvent::LOGOUT: Should be sent after the user has been loged out
    * * UserAuth\UserEvent::REGISTER.'.pre': Should be sent before registration start
    * * UserAuth\UserEvent::REGISTER: Should be sent when the registration is completed and successful
    * * UserAuth\UserEvent::REGISTER_FAILED: Should be sent when the registration failed
    * * UserAuth\UserEvent::RESET_PASSWORD_REQUEST.'.pre': Should be sent before generating the request to reset a password
    * * UserAuth\UserEvent::RESET_PASSWORD_REQUEST: Should be sent after the reset password email was sent
    * * UserAuth\UserEvent::RESET_PASSWORD_HANDLED.'.pre': Should be sent when the user is resetting the password
    * * UserAuth\UserEvent::RESET_PASSWORD_HANDLED: Should be sent after the password was reset
    * * UserAuth\UserEvent::CHANGE_PASSWORD.'.pre': Should be sent when a user change the password
    * * UserAuth\UserEvent::CHANGE_PASSWORD: Should be sent when the user successfully changed the password
    * * UserAuth\UserEvent::CHANGE_PASSWORD.'.err': Should be sent when there was an error changing the password
    * * UserAuth\UserEvent::CONFIRM_EMAIL_HANDLED.'.pre': Should be sent before sending the email to confirm user email address
    * * UserAuth\UserEvent::CONFIRM_EMAIL_HANDLED: Should be sent after the user confirm his/her email
    * * UserAuth\UserEvent::CONFIRM_EMAIL_HANDLED.'.err': Should be sent to report an error confirming the email
    *
    * @return \Laminas\EventManager\EventManagerInterface
    */
    public function getEventManager()
    {
        return $this->eventManager;
    }

}
