<?php
namespace OAuth;

use Laminas\Stdlib\ArrayUtils;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\Segment;
use Laminas\Router\Http\Literal;

class Module implements ConfigProviderInterface
{
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

    public function onBootstrap(MvcEvent $e)
    {
        $application    = $e->getApplication();
        $sm = $application->getServiceManager();
        $eventManager   = $application->getEventManager();

        $router = $sm->get('router');
        $router->addRoute(
            'oauth-server',
            [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/:locale]/oauth',
                    'defaults' => [
                        'controller' => Controller\OAuth2ServerController::class,
                        'action'     => 'token',
                        'locale'     => 'en',
                    ],
                    'contraints'=> [
                        'locale' => 'en|fr',
                    ],
                ],
                'may_terminate' => true,
                'child_routes'=>[
                    'login' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/login',
                            'defaults' => [
                                'action' => 'authorize-login',
                            ],
                        ],
                    ],
                    'authorize' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/authorize',
                            'defaults' => [
                                'action' => 'authorize',
                            ],
                        ],
                    ],
                    'revoke'    => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/revoke',
                            'defaults' => [
                                'action' => 'revoke',
                            ],
                        ],
                    ],
                    'resource'  => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/resource',
                            'defaults' => [
                                'action' => 'resource',
                            ],
                        ],
                    ],
                    'code'      => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/receivecode',
                            'defaults' => [
                                'action' => 'receiveCode',
                            ],
                        ],
                    ],
                    'admin'      => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/admin',
                            'defaults' => [
                                'action' => 'admin',
                            ],
                        ],
                    ],
                ],
            ],
            -8999
        );
        $router->addRoute(
            'oauth-client',
            [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/oauth-login[/:method]',
                    'constraints'=>[
                        'method'=>'(?!return).*',
                    ],
                    'defaults' => [
                        'controller' => Controller\OAuth2ClientController::class,
                        'action'     => 'index',
                        'method'     => '',
                    ],
                ],
                'may_terminate' => true,
                'child_routes'=>[
                    'return' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/return',
                            'defaults' => [
                                'action'     => 'return',
                            ],
                        ],
                    ],
                ],
            ],
            -9000
        );
        $router->addRoute(
            'oauth-js',
            [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/oauth]/js/oauth2.js',
                    'constraints'=>[
                        'method'=>'(?!return).*',
                    ],
                    'defaults' => [
                        'controller' => Controller\OAuth2ClientController::class,
                        'action'     => 'js',
                    ],
                ],
            ],
            -9000
        );

        //*****************************************************
        // get view helper manager
        $viewHelperManager = $sm->get('ViewHelperManager');

        // get 'head script' plugin
        $headScript = $viewHelperManager->get('headScript');
        //$headScript->appendFile('/oauth/js/oauth2.js');

    }
}
