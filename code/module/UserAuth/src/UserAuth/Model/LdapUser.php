<?php
namespace UserAuth\Model;

use \GcNotify\GcNotify;
use \Laminas\Mvc\I18n\Translator as MvcTranslator;
use \Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack as UrlPlugin;
use \UserAuth\Exception\UserConfirmException;
use \Laminas\EventManager\EventManagerInterface as EventManager;
use \UserAuth\Module as UserAuth;
use \UserAuth\Exception as UserException;
use \Laminas\Session\Container;
use \Psr\Log\LoggerInterface;
use \ActiveDirectory\Model\ActiveDirectory;

class LdapUser extends User
{
    private $ldap;
    public function setLdap(ActiveDirectory $obj)
    {
        $this->ldap = $obj;
        return $this;
    }
    protected function getLdap()
    {
        return $this->ldap;
    }

    public function authenticate(String $email, String $password)
    {
        $this->getEventManager()->trigger(UserAuth::EVENT_LOGIN.'.pre', $this, ['email'=>$email]);
        $ad = $this->getLdap();
        $data = $ad->getUserByEmailOrUsername($email, returnFirstElementOnly:true);

        if(!$ad->validateCredentials($data['dn'], $password)) {
            $this->getEventManager()->trigger(UserAuth::EVENT_LOGIN_FAILED, $this, ['email'=>$email, 'userId'=>$data['userId']??null]);
            return false;
        }
        $this->getEventManager()->trigger(UserAuth::EVENT_LOGIN, $this, ['email'=>$email, 'userId'=>$data['userId']]);

        $this->buildLoginSession($data);
        return true;
    }

    public function register(String $email, String $password, ?GcNotify $notify)
    {
        throw new UserException\UserMethodUnavailable();
    }

    public function requestResetPassword(String $email, GcNotify $notify)
    {
        throw new UserException\UserMethodUnavailable();
    }

    public function handleVerifyEmailToken(String $token)
    {
        throw new UserException\UserMethodUnavailable();
    }

    public function validateResetPasswordToken(String $token)
    {
        throw new UserException\UserMethodUnavailable();
    }

    public function changePassword(String $token, String $password)
    {
        throw new UserException\UserMethodUnavailable();
    }

    public function validatePassword(String $password, String $confirmation=null, array $passwordRules=[])
    {
        throw new UserException\UserMethodUnavailable();
    }

    public function getJWT($time = 86400)
    {
        $jwt = $this->getJwtObj();
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        $session = $this->getSessionInfo();
        $payload=[
            'email'=>$session['email'],
            'account'=>$session['account'],
            'dn'=>$session['dn'],
        ];

        return $jwt->generate($header, $payload, $time);
    }
}
