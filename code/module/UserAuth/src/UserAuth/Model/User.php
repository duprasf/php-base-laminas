<?php
namespace UserAuth\Model;

use \GcNotify\GcNotify;
use \Laminas\Mvc\I18n\Translator as MvcTranslator;
use \Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack as UrlPlugin;
use \UserAuth\Exception\UserConfirmException;
use \Laminas\EventManager\EventManagerInterface as EventManager;
use \UserAuth\Module as UserAuth;
use \Laminas\Session\Container;
use \Psr\Log\LoggerInterface;

abstract class User implements UserInterface, \ArrayAccess
{
    protected const ID_FIELD = 'email';

    protected $jwtObj;
    public function setJwtObj(JWT $obj)
    {
        $this->jwtObj = $obj;
        return $this;
    }
    protected function getJwtObj()
    {
        return $this->jwtObj;
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

    protected $logger;
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger=$logger;
        return $this;
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

    public function getUserId()
    {
        $data = $this->getSessionInfo();
        return $data[self::ID_FIELD] ?? null;
    }

    abstract public function authenticate(String $email, String $password);

    /**
    * $userId is still there for backward compatibilty
    *
    * @param mixed $userId
    * @param array $data
    * @return {User|User}
    */
    protected function buildLoginSession(array $data) : self
    {
        $container = new Container('UserAuth');
        $container->exchangeArray($data);

        return $this;
    }
    protected function destroySession() : self
    {
        $container = new Container('UserAuth');
        $container->exchangeArray([]);

        return $this;
    }

    protected function getSessionInfo() : array
    {
        $container = new Container('UserAuth');
        return $container->getArrayCopy();
    }

    public function logout() : self
    {
        $this->getEventManager()->trigger(
            UserAuth::EVENT_LOGOUT.'.pre',
            $this,
            [
                'email'=>$this['email'],
                'userId'=>$this['userId']
            ]
        );
        $this->destroySession();
        $this->getEventManager()->trigger(
            UserAuth::EVENT_LOGOUT,
            $this,
            [
                'email'=>$this['email'],
                'userId'=>$this['userId']
            ]
        );
        return $this;
    }

    public function validateLoginSession($token)
    {
    }

    public function jwtToData($jwt)
    {
        return $this->getJwtObj()->getPayload($jwt);
    }

    public function getJWT($time = 86400)
    {
        $jwt = $this->getJwtObj();
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        $payload = $this->getSessionInfo();
        $payload['id']=$this['userId'];
        return $jwt->generate($header, $payload, $time);
    }

    /**
    * ArrayAccess
    */
     public function offsetExists(mixed $offset): bool
     {
         return isset($this->getSessionInfo()[$offset]);
     }
     public function offsetGet(mixed $offset): mixed
     {
         return $this->getSessionInfo()[$offset] ?? null;
     }
     public function offsetSet(mixed $offset, mixed $value): void
     {
         $this->getSessionInfo()[$offset] = $value;
     }
     public function offsetUnset(mixed $offset): void
     {
         $this->getSessionInfo()[$offset] = null;
         unset($this->getSessionInfo()[$offset]);
     }
}
