<?php

namespace UserAuth\Listener;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\Event;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Session\SessionManager;
use Laminas\Http\Request;

class UserAuthListener
{
    private $headScript;
    public function setHeadScript($obj)
    {
        $this->headScript = $obj;
        return $this;
    }
    protected function getHeadScript()
    {
        return $this->headScript;
    }

    public function attach(EventManagerInterface $eventManager)
    {
        $eventManager->attach(
            MvcEvent::EVENT_RENDER,
            [$this, 'addJavascript'],
            1000
        );
    }

    public function addJavascript(MvcEvent $event)
    {
        $this->getHeadScript()->appendFile('/user-auth/js/Session.js');
        $this->getHeadScript()->appendFile('/user-auth/js/User.js');
    }
}
