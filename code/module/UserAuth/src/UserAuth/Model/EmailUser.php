<?php
namespace UserAuth\Model;

use \PDO;
use GcNotify\GcNotify;
use Psr\Log\LoggerInterface;
use Void\UUID;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack as UrlPlugin;
use Laminas\EventManager\EventManagerInterface as EventManager;
use Laminas\Session\Container;
use UserAuth\Exception\UserException;
use UserAuth\Exception\InvalidCredentialsException;
use UserAuth\Exception\JwtException;
use UserAuth\Exception\JwtExpiredException;
use UserAuth\Module as UserAuth;

class EmailUser extends User implements UserInterface, \ArrayAccess
{
    /**
    * In DbUser, the ID Field is userId
    */
    protected const ID_FIELD = 'email';

    public const VERIFICATION_DONE = 1;
    public const VERIFICATION_COULD_NOT_SEND = 2;
    public const VERIFICATION_EMAIL_SENT = 3;

    protected const TOKEN_TYPE_CONFIRM_EMAIL='confirmEmail';
    protected const TOKEN_TYPE_RESET_PASSWORD='resetPassword';

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
    private $translator=null;
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

    protected $userDb;
    /**
    * Set a "parentDb" used when the child class still use a "parent" user that share user between
    * multiple applications
    *
    * @param PDO $db
    * @return DbUser
    */
    public function setUserDb(PDO $db)
    {
        $this->userDb = $db;
        return $this;
    }
    protected function getUserDb()
    {
        return $this->userDb;
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
        $this->ttl=$ttl;
        return $this;
    }
    protected function getTimeToLive()
    {
        return $this->ttl;
    }

    protected $lang;
    public function setlang(string $lang)
    {
        $this->lang=$lang;
        return $this;
    }
    protected function getLang()
    {
        return $this->lang;
    }

    /**
    * Authenticate/login a user using a database. This particular implementation would use a central
    * DB for user and each app could have a user param, that's why it uses a parentDb for authenticating
    *
    * @param string $email
    * @param string $password
    * @return bool, true if successful false otherwise
    * @throws UserAuth\Exception\InvalidCredentialsException In this implementation, throw exception when credentials are incorrect
    * @throws UserAuth\Exception\UserException this is thrown when no "parentDb" is defined.
    */
    public function authenticate(string $email, string $password) : bool
    {
        // signal that the login process will start
        $this->getEventManager()->trigger(UserAuth::EVENT_LOGIN.'.pre', $this, ['email'=>$email]);

        $pdo = $this->getUserDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a userDb');
        }

        // as a email user, we don't "authenticate" we add the email in the DB with a token
        // that will be sent by email to authenticate the user
        try {
            $pdo->beginTransaction();

            $token = $this->getNewToken();
            $prepared = $pdo->prepare("
                INSERT INTO `userEmailLogin`
                    SET email=:email, token=:token, expiryTimestamp=:expire
                ON DUPLICATE KEY UPDATE
                    token=VALUES(token), expiryTimestamp=VALUES(expiryTimestamp)
            ");

            $ttl = date('Y-m-d H:i:s', time()+$this->getTimeToLive());
            $prepared->bindParam(':email', $email, PDO::PARAM_STR);
            $prepared->bindParam(':token', $token, PDO::PARAM_STR);
            $prepared->bindParam(':expire', $ttl, PDO::PARAM_STR);

            $prepared->execute();
            $data = $prepared->fetch(PDO::FETCH_ASSOC);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollback();
            throw $e;
        }
        // sending the email.
        $notify = $this->getGcNotify();

        if(!$notify || !$notify->readyToSend()) {
            throw new MissingComponentException('GcNotify object is not present or not configure correctly');
        }

        $notify(
            $email,
            'Email Login '.$this->getLang(),
            [
                'appName'=>$this->getTranslator()->translate('Employee Directory'),
                'URL'=>$this->url()->assemble(
                    ['locale'=>$this->getLang(),'token'=>$token,],
                    ['name'=>'locale/directory/login/validate','force_canonical' => true,]
                ),
            ]
        );
        return true;
    }

    /**
    * Load a user from the JWT. The expiry time of the JWT should be checked before allowing this.
    *
    * @param string $jwt the JavaScript Web Token received from the client
    * @return bool, true if successful false otherwise
    * @throws UserAuth\Exception\JwtException If the token is null or invalid
    * @throws UserAuth\Exception\JwtExpiredException If the token is expired
    * @throws UserAuth\Exception\UserException if the ID field is not set in the JWT
    */
    public function loadFromJwt(?string $jwt) : bool
    {
        if($jwt == null) {
            throw new JwtException('JWT is null');
        }
        $data = $this->jwtToData($jwt);
        if(!isset($data[self::ID_FIELD])) {
            throw new UserException('ID field ('.self::ID_FIELD.') does not exists in JWT');
        }
        return $this->_loadUserById($data[self::ID_FIELD]);
    }

    /**
    * Load a user from the Session if the useSession is set to true in userConfig [default false]
    *
    * @return bool, true if successful false otherwise
    * @throws UserAuth\Exception\UserException if the ID field is not set in the JWT
    */
    public function loadFromSession() : bool
    {
        $container = new Container('UserAuth');

        if(!isset($container[self::ID_FIELD])) {
            throw new UserException('ID field ('.self::ID_FIELD.') does not exists in Session');
        }
        return $this->_loadUserById($container[self::ID_FIELD]);
    }

    /**
    * A method used by loadFromJwt and loadFromSession to load the user without validating credentials
    *
    * @param int $id
    * @return bool
    */
    protected function _loadUserById(int $id) : bool
    {
        $pdo = $this->getParentDb();
        $prepared = $pdo->prepare("SELECT userId, email, emailVerified, status FROM `user` WHERE userId = ?");
        $prepared->execute([$id]);
        $data = $prepared->fetch(PDO::FETCH_ASSOC);
        if(!$data) {
            $this->data = [];
            return false;
        }

        // save the data in the user and in the session (if config allows it)
        $this->exchangeArray($data);
        $this->buildLoginSession($data);
        return true;
    }

    /**
    * Register a new user, create a DB entry and send email (depending on config)
    *
    * @param string $email
    * @param string $password
    * @param GcNotify $notify if sending email is required
    * @return int status from DbUser::VERIFICATION_*
    * @throws InvalidPassword thrown if the password does not respect all rules, see getLastPasswordErrors()
    * @throws UserException thrown if the GcNotify object is not set and an email verification is required
    * @see getLastPasswordErrors
    */
    public function register(string $email, string $password, string $confirmPassword, ?GcNotify $notify=null)
    {
        throw new UserException('cannot call '.__METHOD__.' for user of type EmailUser');
    }

    /**
    * request the reset of a password, this will send an email to registrered user with a reset link
    *
    * @param string $email email of the registered user that needs to reset their password
    * @param GcNotify $notify
    * @return string|int the token send by email or DbUser::VERIFICATION_COULD_NOT_SEND if email could not be sent
    * @throws UserException thrown if the GcNotify object is not set and an email verification is required
    * @throws UserException thrown when the email is not in the DB
    */
    public function requestResetPassword(string $email, GcNotify $notify)
    {
        throw new UserException('cannot call '.__METHOD__.' for user of type EmailUser');
    }

    /**
    * Called when the user clicked on the link to reset the password
    *
    * @param mixed $token
    * @param mixed $password
    * @return bool true if successful, throws exception for any other reason
    * @throws UserException When token is invalid
    * @throws UserException Without parentDb
    */
    public function resetPassword(string $token, string $password, string $confirm)
    {
        throw new UserException('cannot call '.__METHOD__.' for user of type EmailUser');
    }

    /**
    * Called when a user changes his/her password. They must provide the current password
    *
    * @param string $email
    * @param string $existingPassword
    * @param string $newPassword
    * @param string $confirmPassword
    */
    public function changePassword(string $email, string $existingPassword, string $newPassword, string $confirmPassword)
    {
        throw new UserException('cannot call '.__METHOD__.' for user of type EmailUser');
    }

    /**
    * When a user click on the link sent to verify email
    *
    * @param string $token
    * @throws UserException
    * @throws UserConfirmException
    * @throws \Exception
    */
    public function handleVerifyEmailToken(string $token)
    {
        $pdo = $this->getUserDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a userDb');
        }

        $prepared = $pdo->prepare("SELECT email, token, expiryTimestamp FROM `userEmailLogin` WHERE token LIKE ?");
        $prepared->execute([$token]);

        $data = $prepared->fetch(PDO::FETCH_ASSOC);

        if(!$data || $data['expiryTimestamp'] < time()) {
            // token not found or expired
            throw new InvalidCredentialsException('Token does not exists or is expired');
        }

        $this->exchangeArray($data);
        // save user data in session if config allows
        // It is much safer to pass the JWT to all request instead of keeping a session
        // but I know not every use case would work with that.
        $this->buildLoginSession($data);

        // signal that the login was successful
        $this->getEventManager()->trigger(UserAuth::EVENT_LOGIN, $this, ['email'=>$data['email']]);

        return true;
    }

    /**
    * Delete a token once it was used
    *
    * @param string $token
    * @return bool true if something was delete false otherwise
    */
    protected function removeToken(string $token)
    {
        $pdo = $this->getUserDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a userDb');
        }

        $prepared = $pdo->prepare("DELETE FROM userEmailLogin WHERE token=? LIMIT 1");
        $prepared->execute([$token]);
        return !!$prepared->rowCount();

    }

    /**
    * generate a unique token. Not saved in the DB, just return a token not in the DB
    *
    * @return string the token
    * @throws UserException if the parentDb is not set
    */
    protected function getNewToken()
    {
        $pdo = $this->getUserDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a userDb');
        }

        // make sure the token is not in the DB already
        $prepared = $pdo->prepare("SELECT 1 FROM userEmailLogin WHERE token LIKE ?");
        do {
            $token = UUID::v4();
            $prepared->execute([$token]);
        } while ($prepared->rowCount());

        return $token;
    }
}
