<?php

namespace OAuth\Listener;

use Laminas\Router\Http\Segment;
use Laminas\Router\Http\Literal;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\Event;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Session\SessionManager;
use Laminas\Http\Request;
use OAuth\Controller\OAuth2ClientController;
use OAuth\Controller\OAuth2ServerController;

class OAuthSetupListener
{
    private $router;
    public function setRouter($obj)
    {
        $this->router = $obj;
        return $this;
    }
    protected function getRouter()
    {
        return $this->router;
    }

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
            MvcEvent::EVENT_BOOTSTRAP,
            [$this, 'addNewRoutes'],
            1000
        );

        $eventManager->attach(
            MvcEvent::EVENT_RENDER,
            [$this, 'addJavascript'],
            1000
        );
    }

    public function addNewRoutes(MvcEvent $event)
    {
        // The routes are added as a listener and not in the module config
        // so I could add the low priority
        $router = $this->getRouter();
        $router->addRoute(
            'oauth-server',
            [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/:locale]/oauth',
                    'defaults' => [
                        'controller' => OAuth2ServerController::class,
                        'action'     => 'token',
                        'locale'     => 'en',
                    ],
                    'contraints' => [
                        'locale' => 'en|fr',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
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
                    'constraints' => [
                        'method' => '(?!return).*',
                    ],
                    'defaults' => [
                        'controller' => OAuth2ClientController::class,
                        'action'     => 'index',
                        'method'     => '',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
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
                    'defaults' => [
                        'controller' => OAuth2ClientController::class,
                        'action'     => 'js',
                    ],
                ],
            ],
            -9000
        );
    }

    public function addJavascript(MvcEvent $event)
    {
        // TODO: disabled until the JS errors are resolved
        //$this->getHeadScript()->appendFile('/oauth/js/oauth2.js');
    }
}
