<?php

declare(strict_types=1);

namespace Application;

use Laminas\Stdlib\ArrayUtils;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\SessionManager;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Container;
use Laminas\Session\Validator;
use Laminas\View\Model\JsonModel;

/**
* Base configuration class for the Application module. This is the namespace for generic features
*/
class Module
{
    /**
    * @ignore default method for Lamnias, no need to be in documentation
    */
    public function getConfig(): array
    {
        $config = include __DIR__ . '/../config/module.config.php';
        foreach(glob(__DIR__ . '/../config/autoload/{,*.}{global,local}.php', GLOB_BRACE) as $file) {
            if(is_readable($file)) {
                $config = ArrayUtils::merge($config, include($file));
            }
        }
        return $config;
    }

    /**
    * @ignore default method for Lamnias, no need to be in documentation
    */
    public function onBootstrap(MvcEvent $e)
    {
        $application = $e->getApplication();
        $serviceManager = $application->getServiceManager();
        $sessionManager = $serviceManager->get(SessionManager::class);

        $eventManager        = $application->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $eventManager->attach(MvcEvent::EVENT_DISPATCH, array($this, 'setLocale'), 100000);
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'setLocale'), 100000);

        $eventManager->getSharedManager()->attach(
            'Laminas\Stdlib\DispatchableInterface',
            MvcEvent::EVENT_DISPATCH,
            array($this, 'onDispatch')
        );
        $eventManager->getSharedManager()->attach(
            '*',
            MvcEvent::EVENT_RENDER,
            array($this, 'updateMetadata'),
            -9000
        );
        $this->bootstrapSession($e);
    }

    /**
    * Initialize the Laminas Session class to be used in other modules
    *
    * @param MvcEvent $e
    * @return null
    */
    public function bootstrapSession(MvcEvent $e)
    {
        $session = $e->getApplication()
            ->getServiceManager()
            ->get(SessionManager::class);
        $session->start();

        $container = new Container('initialized');

        if(isset($container->init)) {
            return;
        }

        $serviceManager = $e->getApplication()->getServiceManager();
        $request        = $serviceManager->get('Request');

        $session->regenerateId(true);
        $container->init          = 1;
        $container->remoteAddr    = $request->getServer()->get('REMOTE_ADDR');
        $container->httpUserAgent = $request->getServer()->get('HTTP_USER_AGENT');

        $config = $serviceManager->get('Config');
        if (!isset($config['session'])) {
            return;
        }

        $sessionConfig = $config['session'];

        if (!isset($sessionConfig['validators'])) {
            return;
        }

        $chain = $session->getValidatorChain();

        foreach ($sessionConfig['validators'] as $validator) {
            switch ($validator) {
                case Validator\HttpUserAgent::class:
                    $validator = new $validator($container->httpUserAgent);
                    break;
                case Validator\RemoteAddr::class:
                    $validator  = new $validator($container->remoteAddr);
                    break;
                default:
                    $validator = new $validator();
                    break;
            }

            $chain->attach('session.validate', array($validator, 'isValid'));
        }
    }

    /**
    * Setup the flash messenger and the controller in the layout
    *
    * @param MvcEvent $event
    * @return Module
    */
    public function onDispatch(MvcEvent $event)
    {
        $controller = $event->getTarget();
        $layout = $controller->layout();
        //$views  = $layout->getChildren();
        $layout->setVariable('controller', $controller);
        $layout->setVariable('flashMessenger', $controller->flashMessenger());
        return $this;
    }

    /**
    * Set locale (lang) in the the application, the router and the service manager
    *
    * @param MvcEvent $e
    */
    public function setLocale(MvcEvent $e)
    {
        $sm = $e->getApplication()->getServiceManager();
        $sm->setAllowOverride(TRUE);
        $route = $e->getRouteMatch();
        $translator = $sm->get('MvcTranslator');
        if(PHP_SAPI != 'cli') {
            $sm->get('router')->setTranslator($translator);
        }

        $lang = '';
        if($sm->has('lang')) {
            $lang = $sm->get('lang');
        }
        if(!key_exists($lang, $sm->get('supportedLang'))) {
            // default language
            $lang = 'en';
            $sm->setService('lang', $lang);
            //$route->setParam('lang', $lang);
        }
        $translator->setLocale($lang);
        setlocale(LC_ALL, $lang.'_CA');
        $sm->setAllowOverride(FALSE);
    }

    /**
    * Set "default" variables (lang, supportedLang and contentSecurityPolicy) to the layout and view object
    *
    * @param MvcEvent $event
    * @return Module
    */
    public function updateMetadata(MvcEvent $event)
    {
        $application = $event->getTarget();
        $service     = $application->getServiceManager();
        $lang        = $service->get('lang');
        $config      = $application->getConfig();
        $request     = $event->getRequest();
        $response    = $event->getResponse();
        $route       = $event->getRouteMatch();
        $layout      = $event->getViewModel();
        $views       = $layout->getChildren();
        $view        = isset($views[0]) ? $views[0] : new \Laminas\View\Model\ViewModel();

        // if we are returning json, skip this process
        if($layout instanceof JsonModel)
        {
            return $this;
        }
        if($route && !$layout->terminate()) {
            $metadata = $view->getVariable('metadata');
            $lang = isset($metadata['lang'])?$metadata['lang']:$lang;
            $layout->setVariable('lang', $lang);
            $view->setVariable('lang', $lang);

            $layout->setVariable('supportedLang', $service->get('supportedLang'));
            $view->setVariable('supportedLang', $service->get('supportedLang'));
            if(isset($metadata['template']) && ($metadata['template'] == 'no-layout' || $metadata['template'] == 'no-view')) {
                $layout->setTemplate('layout/no-layout.phtml');
            } else {

            }
            $layout->setVariable('contentSecurityPolicy', $service->has('contentSecurityPolicy') ? $service->get('contentSecurityPolicy') : null);
        }
    }
}
