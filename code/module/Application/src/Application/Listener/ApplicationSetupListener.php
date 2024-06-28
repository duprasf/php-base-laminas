<?php
namespace Application\Listener;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\Event;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\SessionManager;
use Laminas\Session\Container;
use Laminas\Session\Validator;
use Laminas\Http\Request;


class ApplicationSetupListener
{
    private $session;
    public function setSessionManager(SessionManager $obj)
    {
        $this->session=$obj;
        return $this;
    }
    protected function getSessionManager()
    {
        return $this->session;
    }

    private $request;
    public function setRequest(Request $obj)
    {
        $this->request=$obj;
        return $this;
    }
    protected function getRequest()
    {
        return $this->request;
    }

    private $config;
    public function setConfig($config)
    {
        $this->config=$config;
        return $this;
    }
    protected function getConfig()
    {
        return $this->config;
    }

    public function attach(EventManagerInterface $eventManager)
    {
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $eventManager->attach(
            MvcEvent::EVENT_DISPATCH,
            [$this, 'setLocale'],
            100000
        );
        $eventManager->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            [$this, 'setLocale'],
            100000
        );

        $eventManager->getSharedManager()->attach(
            'Laminas\Stdlib\DispatchableInterface',
            MvcEvent::EVENT_DISPATCH,
            [$this, 'onDispatch']
        );
        $this->startSession();

        $sharedEventManager = $eventManager->getSharedManager();
        // Register the event listener method.
        $sharedEventManager->attach(
            '*',
            MvcEvent::EVENT_RENDER_ERROR,
            [$this, 'onError'],
            100
        );
        $sharedEventManager->attach(
            '*',
            MvcEvent::EVENT_DISPATCH_ERROR,
            [$this, 'onError'],
            100
        );

    }

    public function startSession()
    {
        $session = $this->getSessionManager();
        $session->start();

        $container = new Container('initialized');

        // if already initialized, we are done
        if(isset($container->init)) {
            return;
        }

        $request        = $this->getRequest();

        $session->regenerateId(true);
        $container->init          = 1;
        $container->remoteAddr    = $request->getServer()->get('REMOTE_ADDR');
        $container->httpUserAgent = $request->getServer()->get('HTTP_USER_AGENT');

        $config = $this->getConfig();
        if (!isset($config['session_config'])) {
            return;
        }

        $sessionConfig = $config['session_config'];

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
        $sm->setAllowOverride(true);
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
        $sm->setAllowOverride(false);
    }

    /**
    * Event listener method. SEnd an email for exceptions and errors
    *
    * @param MvcEvent $event
    * @return mixed
    */
    public function onError(MvcEvent $event)
    {
        if(getenv('PHP_DEV_ENV')) {
            // do not report in dev
            return;
        }

        if(!getenv('GC_NOTIFY_ERROR_REPORTING_API_KEY')) {
            // we do not have the GC Notify Key for reporting errors :(
            return;
        }

        // TODO: should set GcNotify as a dependency injection
        $notify = new GcNotify();
        $notify->setErrorReportingKey(getenv('GC_NOTIFY_ERROR_REPORTING_API_KEY'));
        $notify->setAppName(getenv('GC_NOTIFY_ERROR_REPORTING_APP_NAME'));

        $exception = $event->getParam('exception');
        if ($exception) {
            $notify->reportException($exception);
            return;
        }

        $errorMessage = $event->getError();
        $controllerName = $event->getController();
        $notify->reportError([
            'message'=>$event->getError().PHP_EOL.'___'.PHP_EOL.preg_replace('(#(\d+))', '\1)', debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),
            'file'=>$event->getController(),
        ]);

        return;
    }
}
