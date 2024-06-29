<?php

namespace UserAuth\Model;

use PDO;
use ArrayAccess;
use GcNotify\GcNotify;
use Void\UUID;
use Psr\Log\LoggerInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack as UrlPlugin;
use Laminas\EventManager\EventManagerInterface as EventManager;
use Laminas\Session\Container;
use UserAuth\Model\EmailUser;
use UserAuth\Exception\UserException;
use UserAuth\Exception\InvalidCredentialsException;
use UserAuth\Exception\JwtException;
use UserAuth\Exception\JwtExpiredException;
use UserAuth\UserEvent;

class FileEmailUser extends EmailUser
{
    /**
    * In DbUser, the ID Field is userId
    */
    protected const ID_FIELD = 'email';

    public const VERIFICATION_DONE = 1;
    public const VERIFICATION_COULD_NOT_SEND = 2;
    public const VERIFICATION_EMAIL_SENT = 3;

    protected const TOKEN_TYPE_CONFIRM_EMAIL = 'confirmEmail';
    protected const TOKEN_TYPE_RESET_PASSWORD = 'resetPassword';

    // default time to live (TTL) for link to confirm email is 2 hours
    protected const TOKEN_TTL_CONFIRM_EMAIL = 7200;

    /**
    * @var UrlPlugin
    * @internal
    */
    private $urlPlugin;
    /**
    * Set the ViewPlugin UrlPlugin, this is used when generating the links in the emails
    *
    * @param UrlPlugin $url
    * @return DbUser
    */
    public function setUrlPlugin(UrlPlugin $url)
    {
        $this->urlPlugin = $url;
        return $this;
    }
    protected function getUrlPlugin()
    {
        return $this->urlPlugin;
    }
    protected function url()
    {
        return $this->getUrlPlugin();
    }

    /**
    * @var MvcTranslator
    * @internal
    */
    private $translator = null;
    /**
    * Set the MvcTranslator, this is used when generating the links in the emails
    *
    * @param MvcTranslator $mvcTranslator
    * @return DbUser
    */
    public function setTranslator(MvcTranslator $mvcTranslator)
    {
        $this->translator = $mvcTranslator;
        return $this;
    }
    protected function getTranslator()
    {
        return $this->translator;
    }

    protected $verificationRouteName;
    public function setVerificationRouteName(string $name)
    {
        $this->verificationRouteName = $name;
        return $this;
    }
    protected function getVerificationRouteName()
    {
        if(!$this->verificationRouteName) {
            throw new UserException('You are required to set the verification route name!');
        }
        return $this->verificationRouteName;
    }

    protected $userFile;
    public function setUserFile(string $file)
    {
        $this->userFile = $file;
        return $this;
    }
    protected function getUserFile()
    {
        if(!file_exists($this->userFile) || !is_writable($this->userFile) || !is_readable($this->userFile)) {
            throw new UserException('Could not read/write the user file');
        }
        return $this->userFile;
    }

    protected function getUserJson()
    {
        return json_decode(file_get_contents($this->getUserFile()), true);
    }

    protected function setUserJson(array $json)
    {
        file_put_contents($this->getUserFile(), json_encode($json));
        return $this;
    }

    protected $ttl;
    /**
    * Set a "parentDb" used when the child class still use a "parent" user that share user between
    * multiple applications
    *
    * @param PDO $db
    * @return DbUser
    */
    public function setTimeToLive(int $ttl)
    {
        $this->ttl = $ttl;
        return $this;
    }
    protected function getTimeToLive()
    {
        return $this->ttl;
    }

    protected $lang;
    public function setlang(string $lang)
    {
        $this->lang = $lang;
        return $this;
    }
    protected function getLang()
    {
        return $this->lang;
    }

    /**
    * Register a new user, create an entry in the file and send email
    *
    * @param string $email
    * @param string $password
    * @param string $confirmPassword
    * @param GcNotify $notify if you want to specify a specifc notifier
    * @return int status from User::VERIFICATION_*
    * @throws UserException thrown if the GcNotify object is not set in class or param
    * @see getLastPasswordErrors
    */
    public function register(string $email, string $password = '', string $confirmPassword = '', ?GcNotify $notify = null)
    {
        $email = strtolower($email);
        // signal that the login process will start
        $this->getEventManager()->trigger(UserEvent::REGISTER.'.pre', $this, ['email' => $email]);


        // as a email user, we don't "authenticate" we add the email in the file with a token
        // that will be sent by email to authenticate the user
        $json = $this->getUserJson();

        if(!array_key_exists($email, $json)) {
            throw new UserException('Email not authorized');
        }

        $routeName = $this->getVerificationRouteName();

        $token = $this->getNewToken();
        $ttl = date('Y-m-d H:i:s', time() + $this->getTimeToLive());
        $json[$email]['email'] = $email;
        $json[$email]['token'] = $token;
        $json[$email]['expiryTimestamp'] = $ttl;
        $this->setUserJson($json);

        // sending the email.
        $notify = $notify ?: $this->getGcNotify();

        if(!$notify || !$notify->readyToSend()) {
            throw new MissingComponentException('GcNotify object is not present or not configure correctly');
        }

        $return = $notify(
            $email,
            'login-'.$this->getLang(),
            [
                'appName' => $notify->getAppName(),
                'URL' => $this->url()->assemble(
                    ['locale' => $this->getLang(),'token' => $token,],
                    ['name' => $routeName,'force_canonical' => true,]
                ),
            ]
        );

        if($return) {
            return true;
        }

        throw new UserException($notify->lastPage);
    }

    public function handleVerifyEmailToken(string $token)
    {
        $json = $this->getUserJson();

        $data = [];
        foreach($json as $user) {
            if(isset($user['token']) && $user['token'] == $token) {
                $data = $user;
                break;
            }
        }

        if(!$data || $data['expiryTimestamp'] < time()) {
            // token not found or expired
            throw new InvalidCredentialsException('Token does not exists or is expired');
        }

        // removing the existing token so it cannot be used again
        $json[$data['email']]['token'] = '';
        $data['token'] = '';
        $this->exchangeArray($data);
        $jwt = $this->getJwt();
        $json[$data['email']]['jwt'] = $jwt;
        $this->setUserJson($json);

        // save user data in session if config allows
        // It is much safer to pass the JWT to all request instead of keeping a session
        // but I know not every use case would work with that.
        $this->buildLoginSession($data);

        // signal that the login was successful
        $this->getEventManager()->trigger(UserEvent::LOGIN, $this, ['email' => $data['email']]);

        return true;
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
        $payload = ['id' => $this['email']];
        return $payload;
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

    protected function _loadUserById($id): bool
    {
        $json = $this->getUserJson();

        foreach($json as $user) {
            if(isset($user['email']) && $user['email'] == $id) {
                $this->exchangeArray($user);
                $this->buildLoginSession($user);
                return true;
            }
        }
        return false;
    }

    /**
    * generate a unique token. Not saved in the DB, just return a token not in the DB
    *
    * @return string the token
    * @throws UserException if the parentDb is not set
    */
    protected function getNewToken()
    {
        //TODO: check if token already exists
        $token = UUID::v4();
        return $token;
    }
}
