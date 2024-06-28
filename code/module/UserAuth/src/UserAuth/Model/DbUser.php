<?php
namespace UserAuth\Model;

use PDO;
use GcNotify\GcNotify;
use Psr\Log\LoggerInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack as UrlPlugin;
use Laminas\EventManager\EventManagerInterface as EventManager;
use Laminas\Session\Container;
use UserAuth\Exception\UserException;
use UserAuth\Exception\InvalidCredentialsException;
use UserAuth\Exception\InvalidPassword;
use UserAuth\Exception\UserConfirmException;
use UserAuth\Exception\JwtException;
use UserAuth\Exception\JwtExpiredException;
use UserAuth\UserEvent;

class DbUser extends User implements UserInterface, \ArrayAccess
{
    /**
    * In DbUser, the ID Field is userId
    */
    protected const ID_FIELD = 'userId';

    public const STATUS_DELETED = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 2;
    public const STATUS_BLOCKED_BY_ADMIN = 3;

    public const VERIFICATION_DONE = 1;
    public const VERIFICATION_COULD_NOT_SEND = 2;
    public const VERIFICATION_EMAIL_SENT = 3;

    protected const TOKEN_TYPE_CONFIRM_EMAIL='confirmEmail';
    protected const TOKEN_TYPE_RESET_PASSWORD='resetPassword';

    // default time to live (TTL) for link to confirm email is 2 hours
    protected const TOKEN_TTL_CONFIRM_EMAIL = 7200;
    protected const TOKEN_TTL_RESET_PASSWORD = 7200;

    protected $defaultValues = ['emailVerified'=>0, 'status'=>1];
    /**
    * Set the default values for new user. In this instance we have a
    * 'emailVerified' = 0 meaning the user did not verified his/her email yet
    * and 'status' = 1 meaning... whatever you want in your app, the user is active for example
    *
    * @param String $key
    * @param mixed $value
    * @return DbUser
    */
    public function setDefaultValues(String $key, $value)
    {
        $this->defaultValues[$key] = $value;
        return $this;
    }
    /**
    * Return the default value of the specific key or the entire array of default values if
    * no key was provided
    *
    * @param mixed $key
    */
    public function getDefaultValues($key=null)
    {
        if($key) {
            return isset($this->defaultValues[$key]) ? $this->defaultValues[$key] : null;
        }
        return $this->defaultValues;
    }

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

    protected $parentdb;
    /**
    * Set a "parentDb" used when the child class still use a "parent" user that share user between
    * multiple applications
    *
    * @param PDO $db
    * @return DbUser
    */
    public function setParentDb(PDO $db)
    {
        $this->parentdb = $db;
        return $this;
    }
    protected function getParentDb()
    {
        return $this->parentdb;
    }

    protected $tableName;
    public function setTableName(string $name) : self
    {
        $this->tableName = $name;
        return $this;
    }
    protected function getTableName()
    {
        return $this->tableName ?? 'user';
    }

    /**
    * Authenticate/login a user using a database. This particular implementation would use a central
    * DB for user and each app could have a user param, that's why it uses a parentDb for authenticating
    *
    * @param String $email
    * @param String $password
    * @return bool, true if successful false otherwise
    * @throws UserAuth\Exception\InvalidCredentialsException In this implementation, throw exception when credentials are incorrect
    * @throws UserAuth\Exception\UserException this is thrown when no "parentDb" is defined.
    */
    public function authenticate(String $email, String $password) : bool
    {
        // signal that the login process will start
        $this->getEventManager()->trigger(UserEvent::LOGIN.'.pre', $this, ['email'=>$email]);
        $pdo = $this->getParentDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a parentDb');
        }
        // get the correct user row from the DB
        $prepared = $pdo->prepare("SELECT userId, password, status, emailVerified, `".$this->getTableName()."`.* FROM `".$this->getTableName()."` WHERE email LIKE ?");
        $prepared->execute([$email]);
        $data = $prepared->fetch(PDO::FETCH_ASSOC);

        // if there is no data/user or if the password does not match...
        if(!$data || !password_verify($password, $data['password'])) {
            // signal that the login failed and return false
            $this->getEventManager()->trigger(UserEvent::LOGIN_FAILED, $this, ['email'=>$email, 'userId'=>$data['userId']??null]);
            throw new InvalidCredentialsException();
        }
        // remove the password from the data array for security (it is an hash but still, better safe than sorry)
        unset($data['password']);

        $this->exchangeArray($data);
        // save user data in session if config allows
        // It is much safer to pass the JWT to all request instead of keeping a session
        // but I know not every use case would work with that.
        $this->buildLoginSession($data);

        // signal that the login was successful
        $this->getEventManager()->trigger(UserEvent::LOGIN, $this, ['email'=>$email, 'userId'=>$data['userId']]);

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
    * A method used by loadFromJwt and loadFromSession to load the user without validating credentials
    *
    * @param int $id
    * @return bool
    */
    protected function _loadUserById(int $id) : bool
    {
        $pdo = $this->getParentDb();
        $prepared = $pdo->prepare("SELECT userId, email, emailVerified, status FROM `".$this->getTableName()."` WHERE userId = ?");
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
    * @param String $email
    * @param String $password
    * @param GcNotify $notify if sending email is required
    * @return int status from DbUser::VERIFICATION_*
    * @throws InvalidPassword thrown if the password does not respect all rules, see getLastPasswordErrors()
    * @throws UserException thrown if the GcNotify object is not set and an email verification is required
    * @see getLastPasswordErrors
    */
    public function register(String $email, String $password, String $confirmPassword, ?GcNotify $notify)
    {
        // trigger the start of the registration
        $this->getEventManager()->trigger(UserEvent::REGISTER.'.pre', $this, ['email'=>$email, 'userId'=>null]);

        // if the password is invalid, the method throws an Exception
        $this->validatePassword($password, $confirmPassword);

        $result = false;
        $pdo = $this->getParentDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a parentDb');
        }
        try {
            $pdo->beginTransaction();
            $prepared = $pdo->prepare("INSERT INTO `".$this->getTableName()."` SET email=:email,
                emailVerified=:emailVerified,
                password=:password,
                status=:status
            ");
            $data = [
                'email'=>$email,
                'emailVerified'=>$this->getDefaultValues('emailVerified'),
                'password'=>password_hash($password, PASSWORD_DEFAULT),
                'status'=>$this->getDefaultValues('status'),
            ];
            $prepared->execute($data);
            $data['userId'] = $pdo->lastInsertId();

            if($data['emailVerified']) {
                // if the default value is to that the email is already verified (meaning no need to verify)
                // log the user in and return the positive status
                $this->buildLoginSession($data);
                $this->exchangeArray($data);
                return self::VERIFICATION_DONE;
            }
            if(!$notify) {
                // if we need to send a confirmation email but GcNotify is not set, throw an exception
                throw new UserException('Handler for GC Notify not specified');
            }

            // generate a token to be used to validate the email
            $token = $this->generateToken($data['userId'], self::TOKEN_TYPE_CONFIRM_EMAIL, self::TOKEN_TTL_CONFIRM_EMAIL);
            $result = $notify->sendEmail(
                $data['email'],
                'confirm-email-template',
                [
                    'url-en'=>$this->url()->assemble(['locale'=>'en','token'=>$token,], ['name'=>'user/confirm-email','force_canonical' => true,]),
                    'url-fr'=>$this->url()->assemble(['locale'=>'fr','token'=>$token,], ['name'=>'user/confirm-email','force_canonical' => true,]),
                ]
            );

            // if there was no errors, commit all change to the DB
            $pdo->commit();
            // trigger the event that the registration is completed
            $this->getEventManager()->trigger(UserEvent::REGISTER, $this, ['email'=>$data['email'], 'userId'=>$data['userId']]);
        } catch(\Exception $e){
            // roll back anything that was written in the DB
            $pdo->rollBack();
            // if anything went wrong, trigger the event that registration failed and send the Exception in the event
            $this->getEventManager()->trigger(UserEvent::REGISTER_FAILED, $this, ['email'=>$data['email'], 'userId'=>null, 'exception'=>$e]);
            // throw the Exception to be caught by the app
            throw $e;
        }

        if(!$result) {
            // if the email could not be sent but the registration was successful...
            // you can implement that this would also roll back and not accept the registration
            return self::VERIFICATION_COULD_NOT_SEND;
        }
        return self::VERIFICATION_EMAIL_SENT;
    }

    /**
    * request the reset of a password, this will send an email to registrered user with a reset link
    *
    * @param String $email email of the registered user that needs to reset their password
    * @param GcNotify $notify
    * @return String|int the token send by email or DbUser::VERIFICATION_COULD_NOT_SEND if email could not be sent
    * @throws UserException thrown if the GcNotify object is not set and an email verification is required
    * @throws UserException thrown when the email is not in the DB
    */
    public function requestResetPassword(String $email, GcNotify $notify)
    {
        // trigger event that the reset request is starting
        $this->getEventManager()->trigger(UserEvent::RESET_PASSWORD_REQUEST, $this, ['email'=>$email]);
        $db = $this->getParentDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a parentDb');
        }

        // make sure the user exists
        $prepared = $db->prepare("SELECT userId, email FROM `".$this->getTableName()."` WHERE email LIKE ?");
        $prepared->execute([$email]);
        $data = $prepared->fetch(PDO::FETCH_ASSOC);
        if(!$data || strtolower($email) != strtolower($data['email'])) {
            throw new UserException('User not found');
        }


        // generate a token for the reset password link
        $token = $this->generateToken($data['userId'], self::TOKEN_TYPE_RESET_PASSWORD, self::TOKEN_TTL_RESET_PASSWORD);
        if(!$token) {
            // if no token received... throw an Exception
            throw new \Exception('Unknow error, could not get token');
        }

        $result = $notify->sendEmail(
            $data['email'],
            'reset-password-template',
            [
                'appName-en'=>'Health Canada auth service',
                'appName-fr'=>"Service d'identification de Santé Canada",
                'reset-link-en'=>$this->url()->assemble(['locale'=>'en','token'=>$token,], ['name'=>'user/reset-password/handle','force_canonical' => true,]),
                'reset-link-fr'=>$this->url()->assemble(['locale'=>'fr','token'=>$token,], ['name'=>'user/reset-password/handle','force_canonical' => true,]),
            ]
        );
        if(!$result) {
            return self::VERIFICATION_COULD_NOT_SEND;
        }

        return $token;
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
    public function resetPassword(String $token, String $password, String $confirm)
    {
        $pdo = $this->getParentDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a parentDb');
        }

        $userId = $this->getUserIdFromToken($token, self::TOKEN_TYPE_RESET_PASSWORD);
        if(!$userId) {
            throw new UserException('Invalid token');
        }

        $this->getEventManager()->trigger(UserEvent::RESET_PASSWORD_HANDLED.'.pre', $this, ['email'=>null, 'userId'=>$userId]);

        // validatePassword will try exception if errors are found.
        $this->validatePassword($password, $confirm);

        try {
            $pdo->beginTransaction();
            $prepared = $pdo->prepare("UPDATE `".$this->getTableName()."` SET password=:password WHERE userId=:userId");

            $prepared->execute([
                'password'=>password_hash($password, PASSWORD_DEFAULT),
                'userId'=>$userId
            ]);
            // token is single use, make sure it is deleted
            $this->removeToken($token);
            $pdo->commit();
            $this->getEventManager()->trigger(UserEvent::RESET_PASSWORD_HANDLED, $this, ['email'=>null, 'userId'=>$userId]);
            return true;
        } catch(\Exception $e){
            $pdo->rollBack();
            throw $e;
        }
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
        $pdo = $this->getParentDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a parentDb');
        }

        $this->getEventManager()->trigger(UserEvent::CHANGE_PASSWORD.'.pre', $this, ['email'=>$email, 'userId'=>null]);

        // get the correct user row from the DB
        $prepared = $pdo->prepare("SELECT userId, password, status, emailVerified, `".$this->getTableName()."`.* FROM `".$this->getTableName()."` WHERE email LIKE ?");
        $prepared->execute([$email]);
        $data = $prepared->fetch(PDO::FETCH_ASSOC);

        // if there is no data/user or if the password does not match...
        if(!$data || !password_verify($existingPassword, $data['password'])) {
            $this->getEventManager()->trigger(UserEvent::CHANGE_PASSWORD.'.err', $this, ['email'=>$email, 'userId'=>$data['userId']??null]);
            // signal that the login failed and return false
            throw new InvalidPassword();
        }

        // validatePassword will try exception if errors are found.
        $this->validatePassword($newPassword, $confirmPassword);

        try {
            $pdo->beginTransaction();
            $prepared = $pdo->prepare("UPDATE `".$this->getTableName()."` SET password=:password WHERE userId=:userId");

            $prepared->execute([
                'password'=>password_hash($newPassword, PASSWORD_DEFAULT),
                'userId'=>$data['userId'],
            ]);
            // token is single use, make sure it is deleted
            $this->removeToken($token);
            $pdo->commit();
            $this->getEventManager()->trigger(UserEvent::CHANGE_PASSWORD, $this, ['email'=>$email, 'userId'=>$data['userId']]);
            return true;
        } catch(\Exception $e){
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
    * When a user click on the link sent to verify email
    *
    * @param String $token
    * @throws UserException
    * @throws UserConfirmException
    * @throws \Exception
    */
    public function handleVerifyEmailToken(String $token)
    {
        $pdo = $this->getParentDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a parentDb');
        }

        $userId = $this->getUserIdFromToken($token, self::TOKEN_TYPE_CONFIRM_EMAIL);

        $this->getEventManager()->trigger(UserEvent::CONFIRM_EMAIL_HANDLED.'.pre', $this, ['token'=>$token, 'userId'=>$userId]);

        if(!$userId) {
            $this->getEventManager()->trigger(UserEvent::CONFIRM_EMAIL_HANDLED.'.err', $this, ['token'=>$token, 'error'=>'token not found or expired', 'errCode'=>UserConfirmException::CODE_INVALID_TOKEN]);
            throw new UserConfirmException('token not found', UserConfirmException::CODE_INVALID_TOKEN);
        }

        if(!$this->_loadUserById($userId)) {
            $this->getEventManager()->trigger(UserEvent::CONFIRM_EMAIL_HANDLED.'.err', $this, ['token'=>$token, 'error'=>'user does not exists', 'errCode'=>UserConfirmException::CODE_USER_DOES_NOT_EXISTS]);
            throw new UserConfirmException('user does not exists', UserConfirmException::CODE_USER_DOES_NOT_EXISTS);
        }

        if($this['emailVerified']) {
            $this->logout();
            $this->getEventManager()->trigger(UserEvent::CONFIRM_EMAIL_HANDLED.'.err', $this, ['token'=>$token, 'error'=>'email already confirmed', 'errCode'=>UserConfirmException::CODE_EMAIL_ALREADY_CONFIRMED]);
            throw new UserConfirmException('email already confirmed', UserConfirmException::CODE_EMAIL_ALREADY_CONFIRMED);
        }

        if($this['status'] == self::STATUS_BLOCKED_BY_ADMIN || $this['status'] == self::STATUS_DELETED || $this['status'] == self::STATUS_INACTIVE) {
            $this->logout();
            $this->getEventManager()->trigger(UserEvent::CONFIRM_EMAIL_HANDLED.'.err', $this, ['token'=>$token, 'error'=>'User is blocked', 'errCode'=>UserConfirmException::CODE_USER_IS_BLOCKED]);
            throw new UserConfirmException('User is blocked', UserConfirmException::CODE_USER_IS_BLOCKED);
        }

        try {
            $pdo->beginTransaction();
            $prepared = $pdo->prepare("UPDATE `".$this->getTableName()."` SET emailVerified=1 WHERE userId=?");
            $prepared->execute([$userId]);

            $data = $this->getArrayCopy();
            $data['emailVerified'] = 1;
            $this->exchangeArray($data);
            $this->buildLoginSession($data);

            $this->removeToken($token);

            $pdo->commit();
            $this->getEventManager()->trigger(UserEvent::CONFIRM_EMAIL_HANDLED, $this, ['email'=>$this['email'], 'userId'=>$this['userId']??null]);
            return true;

        } catch(\Exception $e) {
            $this->logout();
            $pdo->rollBack();
            $this->getEventManager()->trigger(UserEvent::CONFIRM_EMAIL_HANDLED.'.err', $this, ['token'=>$token, 'error'=>'Database could not be updated correctly to validated the email', 'errCode'=>-1]);
            throw new \Exception('Database could not be updated correctly to validated the email');
        }
    }

    /**
    * Delete a token once it was used
    *
    * @param String $token
    * @return bool true if something was delete false otherwise
    */
    protected function removeToken(String $token)
    {
        $pdo = $this->getParentDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a parentDb');
        }

        $prepared = $pdo->prepare("DELETE FROM userToken WHERE token=? LIMIT 1");
        $prepared->execute([$token]);
        return !!$prepared->rowCount();

    }

    /**
    * Get the userId is the token is valid, still active and of the right type
    *
    * @param String $token
    * @param String $type
    */
    public function getUserIdFromToken(String $token, ?String $type)
    {
        $pdo = $this->getParentDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a parentDb');
        }

        $prepared = $pdo->prepare("
            SELECT userId
            FROM userToken
            WHERE token=:token
                AND expiredAt > :time
                ".($type ? 'AND type LIKE :type':'')."
        ");
        $data = ['token'=>$token, 'time'=>time()];
        if($type) {
            $data['type'] = $type;
        }
        $prepared->execute($data);
        return $prepared->fetchColumn();
    }

    /**
    * Generate a new token for a specific type and an optional time to live
    *
    * @param int $userId
    * @param String $type, should be one of DbUser::TOKEN_TYPE_*
    * @param int $timeToLive in seconds (3600=1hr, 7200=2hrs, 86400=24hrs, 604800=1 week)
    * @return String a new token for the specific type
    * @throws UserException if the parentDb is not set
    */
    protected function generateToken(int $userId, String $type, ?int $timeToLive)
    {
        $pdo = $this->getParentDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a parentDb');
        }

        $token = $this->getNewToken();
        $expiredAt = $timeToLive ? time()+$timeToLive : null;
        $prepared = $pdo->prepare("INSERT INTO userToken
            SET token=:token, userId=:userId, type=:type, expiredAt=:expiredAt
        ");
        $prepared->execute([
            'token'=>$token,
            'userId'=>$userId,
            'type'=>$type,
            'expiredAt'=>$expiredAt,
        ]);
        return $token;
    }

    /**
    * generate a unique token. Not saved in the DB, just return a token not in the DB
    *
    * @return string the token
    * @throws UserException if the parentDb is not set
    */
    protected function getNewToken()
    {
        $pdo = $this->getParentDb();
        if(!$pdo) {
            // cannot use this method if the parentDb was not set
            throw new UserException('You cannot use this parent service without a parentDb');
        }

        // make sure the token is not in the DB already
        $prepared = $pdo->prepare("SELECT 1 FROM userToken WHERE token LIKE ?");
        do {
            $token = sha1(uniqid(time()));
            $prepared->execute([$token]);
        } while ($prepared->rowCount());

        return $token;
    }
}
