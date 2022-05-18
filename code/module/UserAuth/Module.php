<?php
namespace UserAuth;

use \Laminas\ModuleManager\Feature\ConfigProviderInterface;
use \Laminas\Mvc\ModuleRouteListener;
use \Laminas\Mvc\MvcEvent;

class Module implements ConfigProviderInterface
{
    public const EVENT_REGISTER='usr_register';
    public const EVENT_REGISTER_FAILED='usr_register_failed';
    public const EVENT_LOGIN='usr_login';
    public const EVENT_LOGOUT='usr_logout';
    public const EVENT_LOGIN_FAILED='usr_login_failed';
    public const EVENT_RESET_PASSWORD_REQUEST='usr_reset_pwd_request';
    public const EVENT_RESET_PASSWORD_HANDLED='usr_reset_pwd_handled';
    public const EVENT_CONFIRM_EMAIL_HANDLED='usr_confirm_email_handled';
    public const EVENT_CHANGE_PASSWORD='usr_change_password';


    public function getConfig()
    {
        $config = include __DIR__ . '/config/module.config.php';
        foreach(glob(__DIR__ . '/config/autoload/{,*.}{global,local}.php', GLOB_BRACE) as $file) {
            if(is_readable($file)) {
                $config = array_merge_recursive($config, include($file));
            }
        }
        return $config;
    }

    public function getAutoloaderConfig()
    {
        return [
            'Laminas\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php',
            ],
            'Laminas\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }

    public function onBootstrap(MvcEvent $e)
    {

        $application = $e->getApplication();
        $eventManager = $application->getEventManager()->getSharedManager();


        // register listener
        $listener = $application->getServiceManager()->get(Model\UserAudit::class);
        $listener->setEventManager($eventManager);
    }
}
