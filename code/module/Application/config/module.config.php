<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack;

return [
    'router' => [
        'router_class' => TranslatorAwareTreeRouteStack::class,
        'routes' => [
            'root' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/[:locale]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                        'locale'     => 'en',
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
            Controller\Plugin\GetTranslator::class=>Factory\Controller\Plugin\GetTranslatorFactory::class,
            Controller\Plugin\Lang::class=>Factory\Controller\Plugin\LangFactory::class,
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
            "GcNotify"=>Factory\GcNotifyFactory::class,
            'metadataBuilder' => Factory\MetadataBuilderFactory::class,
        ],
        'invokables' => [
            'breadcrumbs' => Model\Breadcrumbs::class,
        ],
    ],
    'view_helpers' => [
        'aliases'=> [
            'url'=>'url-with-lang',
            'UrlHelper'=>View\Helper\UrlFactory::class,
        ],
        'invokables' => [
            'stripTags' => View\Helper\StripTags::class,
            'breadcrumbs' => View\Helper\BreadcrumbsHelper::class,
            'displayFlashMessages' => View\Helper\DisplayFlashMessages::class,
        ],
        'factories' => [
            "getLangSwitchUrl" => Factory\View\Helper\GetLangSwitchUrlFactory::class,
            "completeMetadata" => Factory\View\Helper\CompleteMetadataFactory::class,
            'url-with-lang' => Factory\View\Helper\UrlFactory::class,
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
    'translator' => [
        'locale' => 'en_CA',
        // Application will load text from the 'root' /language folder and
        // assign the domain of 'layout' to those strings
        'translation_file_patterns' => [
            [
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../../../language',
                'pattern'  => 'layout-%s.mo',
                'text_domain'=>'layout',
            ],
            [
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ],
        ],
    ],
];
