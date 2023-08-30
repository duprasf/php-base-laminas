<?php
namespace UserAuth\Model;

use Exception;
use \Psr\Log\LoggerInterface;
use \GcNotify\GcNotify;
use \Laminas\Session\Container;
use \Laminas\Mvc\I18n\Translator as MvcTranslator;
use \Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack as UrlPlugin;
use \Laminas\EventManager\EventManagerInterface as EventManager;
use \Laminas\Ldap\Exception\LdapException;
use \UserAuth\Module as UserAuth;
use \UserAuth\Exception\UserException;
use \UserAuth\Exception\InvalidCredentialsException;
use \UserAuth\Exception\InvalidPassword;
use \UserAuth\Exception\UserConfirmException;
use \UserAuth\Exception\UserMethodUnavailable;
use \UserAuth\Exception\JwtException;
use \UserAuth\Exception\JwtExpiredException;
use \ActiveDirectory\Model\ActiveDirectory;

class LdapUser extends User
{
    private $ldap;
    /**
    * Set the LDAP object that will be used for authentication
    *
    * @param ActiveDirectory $obj
    * @return LdapUser
    */
    public function setLdap(ActiveDirectory $obj)
    {
        $this->ldap = $obj;
        return $this;
    }
    protected function getLdap()
    {
        return $this->ldap;
    }

    /**
    * Authenticate/login a user using a database. This particular implementation would use Active Directory
    * to authenticate and each app could have a user DB with access rights
    *
    * @param String $email, this can be email or account name
    * @param String $password
    * @return bool, true if successful false otherwise
    * @throws UserAuth\Exception\InvalidCredentialsException In this implementation, throw exception when credentials are incorrect
    */
    public function authenticate(String $email, String $password) : bool
    {
        $this->getEventManager()->trigger(UserAuth::EVENT_LOGIN.'.pre', $this, ['email'=>$email]);

        try {
            $ad = $this->getLdap();
            $data = $ad->getUserByEmailOrUsername($email, returnFirstElementOnly:true);
            if(!$data || !isset($data['dn']) || !$ad->validateCredentials($data['dn'], $password)) {
                $this->getEventManager()->trigger(UserAuth::EVENT_LOGIN_FAILED, $this, ['email'=>$email]);
                // can return false or throw an exception, it depends on your implementation
                throw new InvalidCredentialsException();
            }
        } catch (LdapException $e) {
            print 'error with LDAP server';
            exit();
        } catch (Exception $e) {
            throw $e;
        }
        $this->exchangeArray($data);
        // save user data in session if config allows
        // It is much safer to pass the JWT to all request instead of keeping a session
        // but I know not every use case would work with that.
        $this->buildLoginSession($data);

        $this->getEventManager()->trigger(UserAuth::EVENT_LOGIN, $this, ['email'=>$email]);

        return true;
    }

    /**
    * Load a user from the JWT. The expiry time of the JWT should be checked before allowing this.
    *
    * @param String $jwt the JavaScript Web Token received from the client
    * @return bool, true if successful false otherwise
    * @throws UserAuth\Exception\JwtException If the token is null or invalid
    * @throws UserAuth\Exception\JwtExpiredException If the token is expired
    * @throws UserAuth\Exception\UserException if the ID field is not set in the JWT
    */
    public function loadFromJwt(?String $jwt) : bool
    {
        $data = $this->jwtToData($jwt);
        if(!isset($data[self::ID_FIELD])) {
            throw new JwtException('ID field ('.self::ID_FIELD.') does not exists in JWT');
        }
        $ad = $this->getLdap();
        $data = $ad->getUserByEmailOrUsername($data[self::ID_FIELD], returnFirstElementOnly:true);
        if(!$data) {
            $this->exchangeArray([]);
            $this->buildLoginSession([]);
            return false;
        }

        $this->exchangeArray($data);
        $this->buildLoginSession($data);
        return !!$data;

    }

    /**
    * This function is not available in this class
    *
    * @param String $email
    * @param String $password
    * @param GcNotify $notify
    * @throws UserMethodUnavailable
    */
    public function register(String $email, String $password, String $confirmPassword, ?GcNotify $notify)
    {
        throw new UserMethodUnavailable();
    }

    /**
    * This function is not available in this class
    *
    * @param String $email
    * @param String $password
    * @param GcNotify $notify
    * @throws UserMethodUnavailable
    */
    public function requestResetPassword(String $email, GcNotify $notify)
    {
        throw new UserMethodUnavailable();
    }

    /**
    * This function is not available in this class
    *
    * @param String $email
    * @param String $password
    * @param GcNotify $notify
    * @throws UserMethodUnavailable
    */
    public function handleVerifyEmailToken(String $token)
    {
        throw new UserMethodUnavailable();
    }

    /**
    * This function is not available in this class
    *
    * @param String $email
    * @param String $password
    * @param GcNotify $notify
    * @throws UserMethodUnavailable
    */
    public function validateResetPasswordToken(String $token)
    {
        throw new UserMethodUnavailable();
    }

    /**
    * This function is not available in this class
    *
    * @param String $email
    * @param String $password
    * @param GcNotify $notify
    * @throws UserMethodUnavailable
    */
    public function resetPassword(String $token, String $password, String $confirmPassword)
    {
        throw new UserMethodUnavailable();
    }

    /**
    * Called when a user changes his/her password. They must provide the current password
    *
    * @param String $email
    * @param String $existingPassword
    * @param String $newPassword
    * @param String $confirmPassword
    */
    public function changePassword(String $email, String $existingPassword, String $newPassword, String $confirmPassword)
    {
        throw new UserMethodUnavailable();
    }

    /**
    * This function is not available in this class
    *
    * @param String $email
    * @param String $password
    * @param GcNotify $notify
    * @throws UserMethodUnavailable
    */
    public function validatePassword(String $password, String $confirmation=null, array $passwordRules=[])
    {
        throw new UserMethodUnavailable();
    }

    /**
    * This function is called when trying to get the JWT
    *
    * @param int $time, the length of time the JWT will be valid. It should not change anything, but just in case...
    * @return array, the data you want to send to client as part of the JWT
    */
    public function getDataForJWT(int $time = 86400) : array
    {
        $payload=[
            'id' => $this[self::ID_FIELD] ?? null,
            'email'=>$this['email'],
            'account'=>$this['account'],
        ];

        // you must return an array, even an empty array would work, but would be completely useless
        return $payload;
    }
}
