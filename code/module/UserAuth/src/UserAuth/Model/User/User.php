<?php

namespace UserAuth\Model\User;

use Exception;
use ArrayObject;
use Laminas\EventManager\EventManagerInterface as EventManager;
use Laminas\Session\Container;
use UserAuth\UserEvent;
use UserAuth\Model\JWT;
use UserAuth\Exception\UserException;
use UserAuth\Exception\UserExistsException;
use UserAuth\Exception\JwtException;
use UserAuth\Exception\InvalidCredentialsException;
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
        try {
            $result = false;
            try {
                $result = $this->getAuthenticator()->register($data);
            } catch(UserExistsException $e) {
                // if the user already exists in the DB, just continue
            }
            $this->authenticate(...$data);

            $this->getEventManager()->trigger(UserEvent::REGISTER, $this, [$this->getIdField() => $data[$this->getIdField()]]);
        } catch(Exception $e) {
            $this->getEventManager()->trigger(UserEvent::REGISTER.'.err', $this, [$this->getIdField() => $data[$this->getIdField()]]);
            throw $e;
        }
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
        $email = isset($args[0]) ? (filter_var($args[0], FILTER_VALIDATE_EMAIL) ? $args[0] : ''):'';

        if($email) {
            $this->getEventManager()->trigger(
                UserEvent::LOGIN.'.pre',
                $this,
                [$this->getIdField() => $email, 'target' => $email]
            );
        }
        $data = $this->getAuthenticator()->authenticate(...$args);
        if(!$data) {
            $this->getEventManager()->trigger(UserEvent::LOGIN.'.err', $this, [
                $this->getIdField() => $email,
                'target' => $email,
                'error' => $this->getIdField().' not found'
            ]);
            $this->logout();
            return false;
        }
        if(!isset($args['token'])) {
            $this->getEventManager()->trigger(
                UserEvent::LOGIN,
                $this,
                ['target' => $email]
            );
        }
        if(!is_array($data)) {
            return true;
        }
        $this->setArrayAndSession($data);
        return $data;
    }

    public function validateEmail($token)
    {
        $this->getEventManager()->trigger(
            UserEvent::EMAIL_CONFIRMED.'.pre',
            $this,
            ['token' => $token]
        );
        $data = $this->getAuthenticator()->validateToken($token);
        if(!$data) {
            $this->getEventManager()->trigger(UserEvent::EMAIL_CONFIRMED.'.err', $this, [
                'token' => $token,
                'error' => 'Token not found'
            ]);
            $this->logout();
            return false;
        }
        unset($data['token']);
        $this->setArrayAndSession($data);
        $this->saveToSession();
        $results = $this->getEventManager()->trigger(
            UserEvent::EMAIL_CONFIRMED,
            $this,
            [
                'token' => $token,
                $this->getIdField()=>$data[$this->getIdField()],
            ]
        );

        foreach($results as $result) {
            if(!is_array($result)) {
                continue;
            }
            if(isset($result['redirectToRoute'])) {
                return $result;
            }
        }
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
        $id = isset($this[$this->getIdField()]) ? $this[$this->getIdField()] : 0;
        $this->getEventManager()->trigger(UserEvent::LOGOUT.'.pre', $this, [$this->getIdField() => $id, 'target' => $id]);
        $this->getAuthenticator()->logout();
        $this->destroySession();
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
    public function loadFromJwt(?string $jwt=null): bool|array
    {
        $jwt = $jwt?:$this->getJwtFromFactory();
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
    public function getUserId(): ?string
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

    public function setToken(string|int $id, string|array $tokenOrCallback): string
    {
        $tokenField = $this->getAuthenticator()->getEmailTokenFieldName();
        if(!$tokenField) {
            throw new UserException('No token field set');
        }
        $token = $this->getStorage()->findUniqueValue($tokenField, $tokenOrCallback);
        if(!$this->getStorage()->update($id, [$tokenField=>$token])) {
            throw new UserException('Could not save token');
        }
        return $token;
    }

    public function changePassword(string|int $id, string $existing, string $new): bool
    {
        $storage = $this->getStorage();
        $tokenFieldName=$this->getAuthenticator()->getEmailTokenFieldName();
        $existingValues = $storage->read($id, ['password', $tokenFieldName]);
        if($existingValues[$tokenFieldName] && $existing == $existingValues[$tokenFieldName]) {
            if($result = $storage->update($id, ['password'=>password_hash($new, PASSWORD_DEFAULT),$tokenFieldName=>''])) {
                $this->authenticate(...[$this->getIdField()=>$id, 'password'=>$new]);
            }

            return $result;
        }
        if(!password_verify($existing, $existingValues['password'])) {
            throw new InvalidCredentialsException();
        }
        return $storage->update($id, ['password'=>password_hash($new, PASSWORD_DEFAULT)]);
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
    private $jwtFromFactory;
    public function setJwtFromFactory(null|string $jwt): self
    {
        $this->jwtFromFactory = $jwt;
        return $this;
    }
    public function getJwtFromFactory(): null|string
    {
        return $this->jwtFromFactory;
    }

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
    * @return \Laminas\EventManager\EventManagerInterface
    */
    public function getEventManager()
    {
        return $this->eventManager;
    }

}
