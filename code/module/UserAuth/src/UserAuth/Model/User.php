<?php
namespace UserAuth\Model;

use \GcNotify\GcNotify;
use \Laminas\Mvc\I18n\Translator as MvcTranslator;
use \Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack as UrlPlugin;
use \UserAuth\Exception\UserConfirmException;
use \Laminas\EventManager\EventManagerInterface as EventManager;
use \UserAuth\Module as UserAuth;
use \Laminas\Session\Container;

class User implements UserInterface, \ArrayAccess
{
    protected $data = array();
    protected $defaultPasswordRules=[
        'minSize'=>12,
        'atLeastOneLowerCase'=>true,
        'atLeastOneUpperCase'=>true,
        'atLeastOneNumber'=>true,
        'atLeastOneSpecialCharacters'=>true,
        'pattern'=>'([a-zA-Z0-9\{\}\[\]\(\)\/\\\'"`~,;:\.<>\*\^\-@\$%\+\?&!=#_]{12,})i',
    ];

    public const STATUS_DELETED = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 2;
    public const STATUS_BLOCKED_BY_ADMIN = 3;

    public const VERIFICATION_DONE = 1;
    public const VERIFICATION_COULD_NOT_SEND = 2;
    public const VERIFICATION_EMAIL_SENT = 3;

    protected const TOKEN_TYPE_CONFIRM_EMAIL='confirmEmail';
    protected const TOKEN_TYPE_RESET_PASSWORD='resetPassword';

    protected $passwordRules;
    public function setPasswordRules(array $passwordRules)
    {
        $this->passwordRules = array_intersect_key($passwordRules, $this->defaultPasswordRules);
        return $this;
    }
    public function getPasswordRules()
    {
        return $this->passwordRules ?? $this->defaultPasswordRules;
    }

    protected $gcNotifyData;
    public function setGcNotifyData(array $data)
    {
        if(!isset($data['api-key']) || !isset($data['confirm-email-template']) || !isset($data['reset-password-template'])) {
            throw new \Exception('missing GC Notify information');
        }
        $this->gcNotifyData = $data;
        return $this;
    }
    public function getGcNotifyData()
    {
        return $this->gcNotifyData;
    }

    protected $eventManager;
    public function setEventManager(EventManager $manager)
    {
        $this->eventManager = $manager;
        return $this;
    }
    public function getEventManager()
    {
        return $this->eventManager;
    }

    protected $lastPasswordErrors;
    public function getLastPasswordErrors()
    {
        return $this->lastPasswordErrors;
    }

    protected $defaultValues = ['emailVerified'=>0, 'status'=>1];
    public function setDefaultValues($key, $value)
    {
        $this->defaultValues[$key] = $value;
        return $this;
    }
    public function getDefaultValues($key=null)
    {
        if($key) {
            return isset($this->defaultValues[$key]) ? $this->defaultValues[$key] : null;
        }
        return $this->defaultValues;
    }

    protected $parentdb;
    public function setParentDb(\PDO $db)
    {
        $this->parentdb = $db;
        return $this;
    }
    /**
    * @return \PDO
    */
    public function getParentDb()
    {
        return $this->parentdb;
    }

    /**
    * @var UrlPlugin
    */
    protected $urlPlugin;
    public function setUrlPlugin(UrlPlugin $url)
    {
        $this->urlPlugin = $url;
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
    */
    protected $translator=null;
    public function setTranslator(MvcTranslator $mvcTranslator) {
        $this->translator = $mvcTranslator;
        return $this;
    }
    public function getTranslator() {
        return $this->translator;
    }

    public function isLoggedIn() : bool
    {
        return !!$this->getUserId();
    }

    public function getUserId() : ?int
    {
        return $this->data['userId'] ?? null;
    }

    public function authenticate(String $email, String $password)
    {
        $this->getEventManager()->trigger(UserAuth::EVENT_LOGIN.'.pre', $this, ['email'=>$email]);
        $pdo = $this->getParentDb();
        $prepared = $pdo->prepare("SELECT userId, password, status, emailVerified FROM `user` WHERE email LIKE ?");
        $prepared->execute([$email]);
        $data = $prepared->fetch(\PDO::FETCH_ASSOC);
        if($data && password_verify($password, $data['password'])) {
            $this->getEventManager()->trigger(UserAuth::EVENT_LOGIN, $this, ['email'=>$email, 'userId'=>$data['userId']]);
            $this->loadUserById($data['userId']);
            $this->buildLoginSession($data['userId']);
            return true;
        } else {
            $this->getEventManager()->trigger(UserAuth::EVENT_LOGIN_FAILED, $this, ['email'=>$email, 'userId'=>$data['userId']??null]);
            return false;
        }
    }

    public function loadFromSession() : bool
    {
        $container = new Container('UserAuth');
        if($container->userId) {
            return $this->loadUserById($container->userId);
        } else {
            return false;
        }
    }
    protected function buildLoginSession($userId)
    {
        $container = new Container('UserAuth');
        $container->userId=$userId;
    }
    protected function destroySession() : self
    {
        $container = new Container('UserAuth');
        $container->userId=null;
        return $this;
    }
    protected function getSessionInfo() : array
    {
        $container = new Container('UserAuth');
        return $container->toArray();
    }

    protected function loadUserById(int $id) : bool
    {
        $pdo = $this->getParentDb();
        $prepared = $pdo->prepare("SELECT userId, email, emailVerified, status FROM `user` WHERE userId = ?");
        $prepared->execute([$id]);
        $data = $prepared->fetch(\PDO::FETCH_ASSOC);
        if($data) {
            $this->data = $data;
            return true;
        } else {
            $this->data = [];
            return false;
        }
    }

    public function logout() : self
    {
        $this->getEventManager()->trigger(UserAuth::EVENT_LOGOUT.'.pre', $this, ['email'=>$this['email'], 'userId'=>$this['userId']]);
        $this->data = [];
        $this->destroySession();
        $this->getEventManager()->trigger(UserAuth::EVENT_LOGOUT, $this, ['email'=>$this['email'], 'userId'=>$this['userId']]);
        return $this;
    }

    public function validateLoginSession($token)
    {
    }

    public function register(String $email, String $password, ?GcNotify $notify)
    {
        if(!$this->validatePassword($password)) {
            throw new \Exception('Password is not valid');
        }
        $this->getEventManager()->trigger(UserAuth::EVENT_REGISTER.'.pre', $this, ['email'=>$email, 'userId'=>null]);

        $result = false;
        $pdo = $this->getParentDb();
        $pdo->beginTransaction();
        try {
            $prepared = $pdo->prepare("INSERT INTO `user` SET email=:email,
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
                $this->buildLoginSession($data['userId']);
                return self::VERIFICATION_DONE;
            }
            if(!$notify) {
                throw new \Exception('Handler for GC Notify not specified');
            }
            $token = $this->generateToken($data['userId'], self::TOKEN_TYPE_CONFIRM_EMAIL);
            $notifyData = $this->getGcNotifyData();
            $result = $notify->sendEmail(
                $data['email'],
                $notifyData['confirm-email-template'],
                [
                    'url-en'=>$this->url()->assemble(['locale'=>'en','token'=>$token,], ['name'=>'user/confirm-email','force_canonical' => true,]),
                    'url-fr'=>$this->url()->assemble(['locale'=>'fr','token'=>$token,], ['name'=>'user/confirm-email','force_canonical' => true,]),
                ],
                $notifyData['api-key']
            );

            $pdo->commit();
            $this->getEventManager()->trigger(UserAuth::EVENT_REGISTER, $this, ['email'=>$data['email'], 'userId'=>$data['userId']]);
        } catch(\Exception $e){
            $this->getEventManager()->trigger(UserAuth::EVENT_REGISTER_FAILED, $this, ['email'=>$data['email'], 'userId'=>null]);
            $pdo->rollBack();
            throw $e;
        }

        if(!$result) {
            return self::VERIFICATION_COULD_NOT_SEND;
        }
        return self::VERIFICATION_EMAIL_SENT;
    }

    public function requestResetPassword(String $email, GcNotify $notify)
    {
        $this->getEventManager()->trigger(UserAuth::EVENT_RESET_PASSWORD_REQUEST, $this, ['email'=>$email]);
        $db = $this->getParentDb();
        $prepared = $db->prepare("SELECT userId, email FROM `user` WHERE email LIKE ?");
        $prepared->execute([$email]);
        $data = $prepared->fetch(\PDO::FETCH_ASSOC);
        if(strtolower($email) == strtolower($data['email'])) {
            $token = $this->generateToken($data['userId'], self::TOKEN_TYPE_RESET_PASSWORD);
            if($token && $notify) {
                $notifyData = $this->getGcNotifyData();

                $result = $notify->sendEmail(
                    $data['email'],
                    $notifyData['reset-password-template'],
                    [
                        'appName-en'=>'Health Canada auth service',
                        'appName-fr'=>"Service d'identification de Santé Canada",
                        'reset-link-en'=>$this->url()->assemble(['locale'=>'en','token'=>$token,], ['name'=>'user/reset-password/handle','force_canonical' => true,]),
                        'reset-link-fr'=>$this->url()->assemble(['locale'=>'fr','token'=>$token,], ['name'=>'user/reset-password/handle','force_canonical' => true,]),
                    ],
                    $notifyData['api-key']
                );
            }
            return $token;
        }
        return false;
    }

    public function changePassword(String $token, String $password)
    {
        if(!$this->validatePassword($password)) {
            throw new \Exception('Password is not valid');
        }
        $userId = $this->getUserIdFromToken($token);
        if($userId) {
            $this->getEventManager()->trigger(UserAuth::EVENT_RESET_PASSWORD_HANDLED.'.pre', $this, ['email'=>null, 'userId'=>$userId]);

            $pdo = $this->getParentDb();
            $pdo->beginTransaction();
            try {
                $prepared = $pdo->prepare("UPDATE `user` SET password=:password WHERE userId=:userId");

                $prepared->execute([
                    'password'=>password_hash($password, PASSWORD_DEFAULT),
                    'userId'=>$userId
                ]);
                $this->removeToken($token, $pdo);
                $pdo->commit();
                $this->getEventManager()->trigger(UserAuth::EVENT_RESET_PASSWORD_HANDLED, $this, ['email'=>null, 'userId'=>$userId]);
            } catch(\Exception $e){
                $pdo->rollBack();
                throw $e;
            }
            return true;
        }
        return false;
    }

    public function handleVerifyEmailToken(String $token)
    {
        $pdo = $this->getParentDb();
        $prepared = $pdo->prepare("SELECT token, userId, type, dateCreated, usedOn
            FROM userToken
            WHERE token=?
                AND type='confirmEmail'
            ;"
        );
        $prepared->execute([$token]);
        $data = $prepared->fetch(\PDO::FETCH_ASSOC);
        $this->getEventManager()->trigger(UserAuth::EVENT_CONFIRM_EMAIL_HANDLED.'.pre', $this, ['email'=>null, 'userId'=>$data['userId']??null]);

        if(count($data) === 0) {
            $this->getEventManager()->trigger(UserAuth::EVENT_CONFIRM_EMAIL_HANDLED.'.err', $this, ['token'=>$token, 'error'=>'token not found', 'errCode'=>UserConfirmException::CODE_TOKEN_NOT_FOUND]);
            throw new UserConfirmException('token not found', UserConfirmException::CODE_TOKEN_NOT_FOUND);
        }

        if($data['usedOn']) {
            $this->getEventManager()->trigger(UserAuth::EVENT_CONFIRM_EMAIL_HANDLED.'.err', $this, ['token'=>$token, 'error'=>'token used already', 'errCode'=>UserConfirmException::CODE_TOKEN_ALREADY_USED]);
            throw new UserConfirmException('token used already', UserConfirmException::CODE_TOKEN_ALREADY_USED);
        }

        if(time() > strtotime('+5 DAYS', strtotime($data['dateCreated']))) {
            $this->getEventManager()->trigger(UserAuth::EVENT_CONFIRM_EMAIL_HANDLED.'.err', $this, ['token'=>$token, 'error'=>'token expire', 'errCode'=>UserConfirmException::CODE_TOKEN_EXPIRED]);
            throw new UserConfirmException('token expire', UserConfirmException::CODE_TOKEN_EXPIRED);
        }

        if(!$this->loadUserById($data['userId'])) {
            $this->getEventManager()->trigger(UserAuth::EVENT_CONFIRM_EMAIL_HANDLED.'.err', $this, ['token'=>$token, 'error'=>'user does not exists', 'errCode'=>UserConfirmException::CODE_USER_DOES_NOT_EXISTS]);
            throw new UserConfirmException('user does not exists', UserConfirmException::CODE_USER_DOES_NOT_EXISTS);
        }

        if($this['emailVerified']) {
            $this->logout();
            $this->getEventManager()->trigger(UserAuth::EVENT_CONFIRM_EMAIL_HANDLED.'.err', $this, ['token'=>$token, 'error'=>'email already confirmed', 'errCode'=>UserConfirmException::CODE_EMAIL_ALREADY_CONFIRMED]);
            throw new UserConfirmException('email already confirmed', UserConfirmException::CODE_EMAIL_ALREADY_CONFIRMED);
        }

        if($this['status'] == self::STATUS_BLOCKED_BY_ADMIN || $this['status'] == self::STATUS_DELETED || $this['status'] == self::STATUS_INACTIVE) {
            $this->logout();
            $this->getEventManager()->trigger(UserAuth::EVENT_CONFIRM_EMAIL_HANDLED.'.err', $this, ['token'=>$token, 'error'=>'User is blocked', 'errCode'=>UserConfirmException::CODE_USER_IS_BLOCKED]);
            throw new UserConfirmException('User is blocked', UserConfirmException::CODE_USER_IS_BLOCKED);
        }

        try {
            $pdo->beginTransaction();
            $prepared = $pdo->prepare("UPDATE `user` SET emailVerified=1 WHERE userId=?");
            $prepared->execute([$data['userId']]);
            $this['emailVerified'] = 1;

            $prepared = $pdo->prepare("UPDATE `userToken` SET usedOn=CURRENT_TIMESTAMP WHERE token=?");
            $prepared->execute([$token]);
            $pdo->commit();
            $this->getEventManager()->trigger(UserAuth::EVENT_CONFIRM_EMAIL_HANDLED, $this, ['email'=>null, 'userId'=>$data['userId']??null]);
        } catch(\Exception $e) {
            $this->logout();
            $pdo->rollBack();
            $this->getEventManager()->trigger(UserAuth::EVENT_CONFIRM_EMAIL_HANDLED.'.err', $this, ['token'=>$token, 'error'=>'Database could not be updated correctly to validated the email', 'errCode'=>0]);
            throw new \Exception('Database could not be updated correctly to validated the email');
        }

        return true;
    }

    public function validateResetPasswordToken(String $token)
    {
        $this->getEventManager()->trigger(UserAuth::EVENT_RESET_PASSWORD_HANDLED, $this, ['email'=>null, 'userId'=>null]);
    }

    public function validatePassword(String $password, String $confirmation=null, array $passwordRules=[])
    {
        $translator = $this->getTranslator();
        $passwordRules = array_intersect_key($passwordRules, $this->defaultPasswordRules);
        if(count($passwordRules) === 0) {
            $passwordRules = $this->getPasswordRules();
        }

        $errors=[];
        if(isset($passwordRules['minSize']) && strlen($password) < $passwordRules['minSize']) {
            $errors['minSize'] = [
                'message'=>sprintf($translator->translate('Minimum size of your password must be %d characters.'), $passwordRules['minSize']),
                'field'=>'password'
            ];
        }
        if(isset($passwordRules['atLeastOneLowerCase']) && !preg_match('([a-z])', $password)) {
            $errors['atLeastOneLowerCase'] = [
                'message'=>$translator->translate('Your password must containt at least one lower case letter.'),
                'field'=>'password'
            ];
        }
        if(isset($passwordRules['atLeastOneUpperCase']) && !preg_match('([A-Z])', $password)){
            $errors['atLeastOneUpperCase'] = [
                'message'=>$translator->translate('Your password must containt at least one upper case letter.'),
                'field'=>'password'
            ];
        }
        if(isset($passwordRules['atLeastOneNumber']) && !preg_match('([0-9])', $password)){
            $errors['atLeastOneNumber'] = [
                'message'=>$translator->translate('Your password must containt at least one number.'),
                'field'=>'password'
            ];
        }
        if(isset($passwordRules['atLeastOneSpecialCharacters']) && !preg_match('(['.preg_quote($passwordRules['atLeastOneSpecialCharacters']).'])', $password)){
            $errors['atLeastOneSpecialCharacters'] = [
                'message'=>$translator->translate('Your password must containt at least one special character.'),
                'field'=>'password'
            ];
        }
        if(isset($passwordRules['additionalRules']) && isset($passwordRules['additionalRulesCallback']) && !call_user_func($passwordRules['additionalRulesCallback'], $password)) {
            $errors['additionalRules'] = [
                'message'=>$translator->translate($passwordRules['additionalRulesErrorMsg']??$passwordRules['additionalRules']),
                'field'=>'password'
            ];
        }
        if($confirmation && $password !== $confirmation) {
            $errors['confirmDoesNotMatch'] = [
                'message'=>$translator->translate('The password and confirmation do not match.'),
                'field'=>'confirmPassword'
            ];
        }
        $this->lastPasswordErrors = $errors;
        return !count($errors);
    }

    protected function removeToken($token, ?\PDO $pdo=null)
    {
        if(!$pdo) {
            $pdo = $this->getParentDb();
        }

        $prepared = $pdo->prepare("DELETE FROM userToken WHERE token=? LIMIT 1");
        $prepared->execute([$token]);
        return $prepared->rowCount();

    }

    public function getUserIdFromToken(String $token)
    {
        $pdo = $this->getParentDb();
        $prepared = $pdo->prepare("SELECT userId FROM userToken WHERE token=?");
        $prepared->execute([$token]);
        return $prepared->fetchColumn();
    }

    protected function generateToken(int $userId, $type)
    {
        $pdo = $this->getParentDb();
        $token = $this->getDbToken();
        $prepared = $pdo->prepare("INSERT INTO userToken SET token=:token, userId=:userId, type=:type");
        $prepared->execute([
            'token'=>$token,
            'userId'=>$userId,
            'type'=>$type,
        ]);
        return $token;
    }

    /**
    * generate a unique token
    *
    * @return string the token
    */
    protected function getDbToken()
    {
        $pdo = $this->getParentDb();
        $prepared = $pdo->prepare("SELECT 1 FROM userToken WHERE token LIKE ?");

        do {
            $token = sha1(uniqid(time()));
            $prepared->execute([$token]);
        } while ($prepared->rowCount());

        return $token;
    }

    public function jwtToData($jwt)
    {
        $jwt = $auth ? str_replace('Bearer ', '', $auth->getFieldValue()) : null;
        $data = null;
        try {
            if($jwt) {
                // still not working since we don't have a way to use the same user obj in all apps.
                //$dataExtracted = json_decode($controller->getUserObject()->getJwtPayload($jwt), true);
                if($dataExtracted && isset($dataExtracted['exp']) && $dataExtracted['exp'] > time()) {
                    $data = $dataExtracted;
                }
                $dataExtracted = null;
            }
        }
        catch(\Exception $e) {
        }

        return $data;
    }

    /**
    * ArrayAccess
    */
     public function offsetExists(mixed $offset): bool
     {
         return isset($this->data[$offset]);
     }
     public function offsetGet(mixed $offset): mixed
     {
         return $this->data[$offset] ?? null;
     }
     public function offsetSet(mixed $offset, mixed $value): void
     {
         $this->data[$offset] = $value;
     }
     public function offsetUnset(mixed $offset): void
     {
         $this->data[$offset] = null;
         unset($this->data[$offset]);
     }
}
