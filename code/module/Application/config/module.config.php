<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'root' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/[:locale]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                    'constraints'=>[
                        'locale'=>'en|fr',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            Controller\Plugin\GetTranslator::class=>Controller\Plugin\Factory\GetTranslatorFactory::class,
            Controller\Plugin\Lang::class=>Controller\Plugin\Factory\LangFactory::class,
        ],
        'aliases' => [
            'getTranslator' => Controller\Plugin\GetTranslator::class,
            'lang' => Controller\Plugin\Lang::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            'lang'=>function($sm) {
                $route = $sm->get('router')->match($sm->get('Request'));
                $lang  = $route ? ($route->getParam('locale', 'en') ?: $route->getParam('lang', 'en')) : 'en';
                return $lang;
            },
            SessionManager::class => function ($container) {
                $config = $container->get('config');
                if (! isset($config['session'])) {
                    $sessionManager = new SessionManager();
                    Container::setDefaultManager($sessionManager);
                    return $sessionManager;
                }

                $session = $config['session'];

                $sessionConfig = null;
                if (isset($session['config'])) {
                    $class = isset($session['config']['class'])
                        ?  $session['config']['class']
                        : SessionConfig::class;

                    $options = isset($session['config']['options'])
                        ?  $session['config']['options']
                        : [];

                    $sessionConfig = new $class();
                    $sessionConfig->setOptions($options);
                }

                $sessionStorage = null;
                if (isset($session['storage'])) {
                    $class = $session['storage'];
                    $sessionStorage = new $class();
                }

                $sessionSaveHandler = null;
                if (isset($session['save_handler'])) {
                    // class should be fetched from service manager
                    // since it will require constructor arguments
                    $sessionSaveHandler = $container->get($session['save_handler']);
                }

                $sessionManager = new SessionManager(
                    $sessionConfig,
                    $sessionStorage,
                    $sessionSaveHandler
                );

                Container::setDefaultManager($sessionManager);
                return $sessionManager;
            },
            "GcNotify"=>'\Application\Factory\GcNotifyFactory',
            'metadataBuilder' => '\Application\Factory\MetadataBuilderFactory',
        ],
        'invokables' => [
            'breadcrumbs' => '\Application\Model\Breadcrumbs',
        ],
    ],
    'view_helpers' => [
        'aliases'=> [
            'url'=>'url-with-lang',
            'UrlHelper'=>View\Helper\UrlFactory::class,
        ],
        'invokables' => [
            'stripTags' => '\Application\View\Helper\StripTags',
            'breadcrumbs' => '\Application\View\Helper\BreadcrumbsHelper',
            'displayFlashMessages' => '\Application\View\Helper\DisplayFlashMessages',
        ],
        'factories' => [
            "getLangSwitchUrl" => '\Application\View\Helper\Factory\GetLangSwitchUrlFactory',
            "completeMetadata" => '\Application\View\Helper\Factory\CompleteMetadataFactory',
            'url-with-lang' => View\Helper\UrlFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => false,
        'display_exceptions'       => false,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/canada.ca.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    /*
    'translator' => [
        'locale' => 'en_CA',
        'translation_file_patterns' => [
            [
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ],
        ],
    ],
    /**/
];
