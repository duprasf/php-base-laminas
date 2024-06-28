<?php
namespace UserAuth;

use Laminas\Stdlib\ArrayUtils;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\Segment;

class Module implements ConfigProviderInterface
{
    public const EVENT_REGISTER='user.register';
    public const EVENT_REGISTER_FAILED='user.register_failed';
    public const EVENT_LOGIN='user.login';
    public const EVENT_LOGOUT='user.logout';
    public const EVENT_LOGIN_FAILED='user.login_failed';
    public const EVENT_RESET_PASSWORD_REQUEST='user.reset_pwd_request';
    public const EVENT_RESET_PASSWORD_HANDLED='user.reset_pwd_handled';
    public const EVENT_CONFIRM_EMAIL_HANDLED='user.confirm_email_handled';
    public const EVENT_EMAIL_SENT='user.email_sent';
    public const EVENT_CHANGE_PASSWORD='user.change_password';


    public function getConfig()
    {
        $config = include __DIR__ . '/config/module.config.php';
        foreach(glob(__DIR__ . '/config/autoload/{,*.}{global,local}.php', GLOB_BRACE) as $file) {
            if(is_readable($file)) {
                $config = ArrayUtils::merge($config, include($file));
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
}
