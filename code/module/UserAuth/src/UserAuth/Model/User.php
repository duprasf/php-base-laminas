<?php

namespace UserAuth\Model;

use GcNotify\GcNotify;
use Psr\Log\LoggerInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack as UrlPlugin;
use Laminas\EventManager\EventManagerInterface as EventManager;
use Laminas\Session\Container;
use UserAuth\Exception\UserConfirmException;
use UserAuth\Model\JWT;
use UserAuth\UserEvent;

abstract class User extends \ArrayObject implements UserInterface
{
    /**
    * Which field represent the unique identifier of the user (ex: email, userId, etc.)
    */
    protected const ID_FIELD = 'email';

    protected $gcNotifyObj;
    public function setGcNotify(GcNotify $obj): self
    {
        $this->gcNotifyObj = $obj;
        return $this;
    }
    public function getGcNotify()
    {
        return $this->gcNotifyObj;
    }

    /**
    * This is the JWT object that is use when the app is using an API
    *
    * @var JWT
    * @internal
    */
    protected $jwtObj;
    /**
    * Set the JWT object (should be used in the factory)
    *
    * @param JWT $obj
    * @return User
    */
    public function setJwtObj(JWT $obj): self
    {
        $this->jwtObj = $obj;
        return $this;
    }
    /**
    * Return the JWT object
    *
    * @return JWT
    */
    protected function getJwtObj()
    {
        return $this->jwtObj;
    }

    protected $sessionLength;
    public function setSessionLength(int $length): self
    {
        $this->sessionLength = $length;
        return $this;
    }
    protected function getSessionLength()
    {
        return $this->sessionLength;
    }

    /**
    * @var EventManager
    * @internal
    */
    protected $eventManager;
    /**
    * should be used in the factory
    *
    * @param EventManager $manager
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
    * @return EventManager
    */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
    * @var LoggerInterface
    * @internal
    */
    protected $logger;
    /**
    * Set the logger interface that will be used to log all activity of this user class
    *
    * @param LoggerInterface $logger
    * @return User
    */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
    * @var array
    * @internal
    */
    private $userconfig;
    /**
    * Configuration for the user object.
    *
    * @param array $config
    * @return User
    */
    public function setUserConfig(array $config)
    {
        $this->userconfig = $config;
        return $this;
    }
    /**
    * Get the configuration, if you specify a key, only that particular key will be returned
    *
    * @param mixed $key
    * @return array
    */
    public function getUserConfig($key = null)
    {
        if(!$key) {
            return $this->userconfig;
        }
        return $this->userconfig[$key] ?? null;
    }

    /**
    * Default rules for a valid password, this can be overwritten in config
    *
    * @var mixed
    */
    protected $defaultPasswordRules = [
        'minSize' => 12,
        'atLeastOneLowerCase' => true,
        'atLeastOneUpperCase' => true,
        'atLeastOneNumber' => true,
        'atLeastOneSpecialCharacters' => true,
        'pattern' => '([a-zA-Z0-9\{\}\[\]\(\)\/\\\'"`~,;:\.<>\*\^\-@\$%\+\?&!=#_]{12,})i',
    ];

    /**
    * @var array
    * @internal
    */
    private $passwordRules;
    /**
    * Set the specific password rules for this instance
    *
    * @param array $passwordRules
    * @return DbUser
    */
    public function setPasswordRules(array $passwordRules)
    {
        $this->passwordRules = array_intersect_key($passwordRules, $this->defaultPasswordRules);
        return $this;
    }
    /**
    * Get this password rules or the default rules if none was passed to the setPasswordRules()
    *
    * @return array
    */
    public function getPasswordRules()
    {
        return $this->passwordRules ?? $this->defaultPasswordRules;
    }

    /**
    * @var array
    * @internal
    */
    protected $lastPasswordErrors;
    /**
    * Return the last error encountered when validating the password againts the rules
    *
    * @return array
    */
    public function getLastPasswordErrors()
    {
        return $this->lastPasswordErrors;
    }

    /**
    * Return true if logged in, false otherwise. By default
    *
    * @return bool, return true if logged in, false otherwise.
    */
    public function isLoggedIn(): bool
    {
        return !!$this->getUserId();
    }

    /**
    * Returns the ID field defined in the const ID_FIELD of the class
    *
    * @return mixed value of self::ID_FIELD
    */
    public function getUserId()
    {
        return $this[self::ID_FIELD] ?? null;
    }

    /**
    * Synonym of authenticate() function
    *
    * @param String $email, the var is called email but this is the unique identifyer of the user
    * @param String $password
    * @return bool, true if successful false otherwise
    * @throws InvalidCredentialsException Depending on implementation, classes can throw exception when credentials are incorrect
    */
    public function login(String $email, String $password): bool
    {
        return $this->authenticate($email, $password);
    }

    /**
    * Authenticate user with an ID (usually email) and password
    *
    * @param String $email, the var is called email but this is the unique identifyer of the user
    * @param String $password
    * @return bool, true if successful false otherwise
    * @throws InvalidCredentialsException Depending on implementation, classes can throw exception when credentials are incorrect
    */
    abstract public function authenticate(String $email, String $password): bool;
    /**
    * Load a user from the JWT. The expiry time of the JWT should be checked before allowing this.
    *
    * @param String $jwt the JavaScript Web Token received from the client
    * @return bool, true if successful false otherwise
    * @throws UserAuth\Exception\JwtException If the token is null or invalid
    * @throws UserAuth\Exception\JwtExpiredException If the token is expired
    * @throws UserAuth\Exception\UserException if the ID field is not set in the JWT
    */
    abstract public function loadFromJwt(?String $jwt): bool;

    /**
    * Called when a user changes his/her password. They must provide the current password
    *
    * @param String $email
    * @param String $existingPassword
    * @param String $newPassword
    * @param String $confirmPassword
    */
    abstract public function changePassword(String $email, String $existingPassword, String $newPassword, String $confirmPassword);

    /**
    * Register a new user, create a DB entry and send email (depending on config)
    *
    * @param String $email
    * @param String $password
    * @param GcNotify $notify if sending email is required
    * @return int status from self::VERIFICATION_*
    * @throws InvalidPassword thrown if the password does not respect all rules, see getLastPasswordErrors()
    * @throws UserException thrown if the GcNotify object is not set and an email verification is required
    * @see getLastPasswordErrors
    */
    abstract public function register(String $email, String $password, String $confirmPassword, ?GcNotify $notify);


    /**
    * Validate the password and confirmation using the provided $passwordRules arg, set in setPasswordRules() or the default rules
    *
    * @param String $password
    * @param String $confirmation
    * @param array $passwordRules specific rules that overwrite the internal or default one
    * @return mixed
    */
    public function validatePassword(String $password, String $confirmation = null, array $passwordRules = [])
    {
        if($confirmation && $password !== $confirmation) {
            throw new InvalidConfirmationPassword(
                $translator->translate('The password and confirmation do not match.')
            );
        }

        $translator = $this->getTranslator();

        // only accept rules that exists in defaultPasswordRules
        $passwordRules = array_intersect_key($passwordRules, $this->defaultPasswordRules);
        if(count($passwordRules) === 0) {
            // if no rules passed by arg, get the one setPasswordRules()
            $passwordRules = $this->getPasswordRules();
        }

        $errors = [];
        if(isset($passwordRules['minSize']) && strlen($password) < $passwordRules['minSize']) {
            $errors['minSize'] = [
                'message' => sprintf($translator->translate('Minimum size of your password must be %d characters.', 'userAuth'), $passwordRules['minSize']),
                'field' => 'password'
            ];
        }
        if(isset($passwordRules['atLeastOneLowerCase']) && !preg_match('([a-z])', $password)) {
            $errors['atLeastOneLowerCase'] = [
                'message' => $translator->translate('Your password must containt at least one lower case letter.', 'userAuth'),
                'field' => 'password'
            ];
        }
        if(isset($passwordRules['atLeastOneUpperCase']) && !preg_match('([A-Z])', $password)) {
            $errors['atLeastOneUpperCase'] = [
                'message' => $translator->translate('Your password must containt at least one upper case letter.', 'userAuth'),
                'field' => 'password'
            ];
        }
        if(isset($passwordRules['atLeastOneNumber']) && !preg_match('([0-9])', $password)) {
            $errors['atLeastOneNumber'] = [
                'message' => $translator->translate('Your password must containt at least one number.', 'userAuth'),
                'field' => 'password'
            ];
        }
        if(isset($passwordRules['atLeastOneSpecialCharacters']) && !preg_match('(['.preg_quote($passwordRules['atLeastOneSpecialCharacters']).'])', $password)) {
            $errors['atLeastOneSpecialCharacters'] = [
                'message' => $translator->translate('Your password must containt at least one special character.', 'userAuth'),
                'field' => 'password'
            ];
        }
        if(isset($passwordRules['additionalRules'])
            && isset($passwordRules['additionalRulesCallback'])
            && function_exists($passwordRules['additionalRulesCallback'])
            && !call_user_func($passwordRules['additionalRulesCallback'], $password)
        ) {
            $errors['additionalRules'] = [
                'message' => $translator->translate($passwordRules['additionalRulesErrorMsg'] ?? $passwordRules['additionalRules'], 'userAuth'),
                'field' => 'password'
            ];
        }
        $this->lastPasswordErrors = $errors;
        if(count($errors)) {
            throw new InvalidPassword();
        }
        return true;
    }

    /**
    * Load a user from the Session if the useSession is set to true in userConfig [default false]
    *
    * @return bool, true if successful false otherwise
    */
    public function loadFromSession(): bool
    {
        $container = new Container('UserAuth');
        if(!isset($container[self::ID_FIELD])) {
            return false;
        }
        $data = $container->getArrayCopy();

        if($data['exp'] < time()) {
            $container->exchangeArray([]);
            return false;
        }
        $this->exchangeArray($data);
        return true;
    }

    /**
    * Set the data in the session if the useSession is set to true in userConfig [default false]
    *
    * @param array $data
    * @return User
    */
    protected function buildLoginSession(array $data): self
    {

        if($this->getUserConfig('useSession')) {
            $container = new Container('UserAuth');
            if(!isset($data['exp'])) {
                $data['iat'] = time();
                $data['exp'] = time() + $this->getSessionLength();
            }
            $container->exchangeArray($data);
        }

        return $this;
    }

    /**
    * Destroy the session
    *
    * @return User
    */
    protected function destroySession(): self
    {
        $container = new Container('UserAuth');
        $container->exchangeArray([]);

        return $this;
    }

    /**
    * Return all the session data
    *
    * @return array
    */
    protected function getSessionInfo(): array
    {
        $container = new Container('UserAuth');
        return $container->getArrayCopy();
    }

    /**
    * Log the user out and destroy the session
    *
    * @return User
    */
    public function logout(): self
    {
        $data = $this->getArrayCopy();
        // get the email and user ID
        $email = $data['email'] ?? null;
        $userId = $data[self::ID_FIELD] ?? null;

        // trigger an event that the user is about to logout
        $this->getEventManager()->trigger(
            UserEvent::LOGOUT.'.pre',
            $this,
            [
                'email' => $email,
                'userId' => $userId
            ]
        );

        // destroy the session and local storage
        $this->destroySession();
        $this->exchangeArray([]);

        // trigger an event that the logout was successful
        $this->getEventManager()->trigger(
            UserEvent::LOGOUT,
            $this,
            [
                'email' => $email,
                'userId' => $userId
            ]
        );
        return $this;
    }

    /**
    * Get the content of the Javascript Web Token (when using API)
    *
    * @param String $jwt
    * @return array containing the content of the JWT
    * @throws UserAuth\Exception\JwtException If the token is null or invalid
    * @throws UserAuth\Exception\JwtExpiredException If the token is expired
    */
    public function jwtToData($jwt)
    {
        return $this->getJwtObj()->getPayload($jwt);
    }

    /**
    * Should return the data to be included in the JWT. This is meant to be overwritten if needed
    * By default, the entire user data set is included and will add a fields called 'id'
    * containing the content of self::ID_FIELD if 'id' was not defined
    *
    * @param int $time, the length of time the JWT will be valid. It should not change anything, but just in case...
    * @return array, the data you want to send to client as part of the JWT
    */
    public function getDataForJWT(int $time = 86400): array
    {
        $payload = $this->getArrayCopy();
        if(!isset($payload['id'])) {
            $payload['id'] = $this[self::ID_FIELD] ?? null;
        }
        return $payload;
    }

    /**
    * Generate and return the Javascript Web Token (when using API)
    *
    * @param mixed $time How long the token should be valid for in seconds (86400=24hrs)
    */
    public function getJWT($time = 86400)
    {
        $jwt = $this->getJwtObj();
        // get the payload from getDataForJWT() which should be overwritten by the child class
        $payload = $this->getDataForJWT($time);
        return $jwt->generate($payload, $time);
    }
}
