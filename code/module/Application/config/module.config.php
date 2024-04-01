<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack;

$return = [
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
            'basescript' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '[/application]/js/base[script].js',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'basescript',
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
        'invokables' => [
            Controller\Plugin\SetApiResponseHeaders::class=>Controller\Plugin\SetApiResponseHeaders::class,
        ],
        'factories' => [
            Controller\Plugin\GetTranslator::class=>Factory\Controller\Plugin\GetTranslatorFactory::class,
            Controller\Plugin\GetUrlHelper::class=>Factory\Controller\Plugin\GetUrlHelperFactory::class,
            Controller\Plugin\Lang::class=>Factory\Controller\Plugin\LangFactory::class,
        ],
        'aliases' => [
            'getTranslator' => Controller\Plugin\GetTranslator::class,
            'getUrlHelper' => Controller\Plugin\GetUrlHelper::class,
            'lang' => Controller\Plugin\Lang::class,
            'getLang' => Controller\Plugin\Lang::class,
            'setApiResponseHeaders'=>Controller\Plugin\SetApiResponseHeaders::class,
            'setResponseHeaders'=>Controller\Plugin\SetApiResponseHeaders::class,
        ],
    ],
    'service_manager' => [
        'aliases'=>[
            "GcNotify"=>GcNotify::class,
            'metadataBuilder' => Model\MetadataBuilder::class,
        ],
        'factories' => [
            'lang'=>Factory\LangFactory::class,
            'domain'=>Factory\DomainFactory::class,
            Model\MetadataBuilder::class => Factory\MetadataBuilderFactory::class,
            SessionManager::class => Factory\SessionManagerFactory::class,
            GcNotify::class=>Factory\GcNotifyFactory::class,
            "filesize-suffixes"=>Factory\FilesizeSuffixesFactory::class,
        ],
        'invokables' => [
            'breadcrumbs' => Model\Breadcrumbs::class,
        ],
    ],
    'view_helpers' => [
        'aliases'=> [
            'url'=>View\Helper\Url::class,
            'url-with-lang'=>View\Helper\Url::class,
            'UrlHelper'=>View\Helper\Url::class,
            'headScript'=>View\Helper\HeadScript::class,
        ],
        'invokables' => [
            'stripTags' => View\Helper\StripTags::class,
            'breadcrumbs' => View\Helper\BreadcrumbsHelper::class,
            'displayFlashMessages' => View\Helper\DisplayFlashMessages::class,
        ],
        'factories' => [
            "getLangSwitchUrl" => Factory\View\Helper\GetLangSwitchUrlFactory::class,
            "completeMetadata" => Factory\View\Helper\CompleteMetadataFactory::class,
            View\Helper\Url::class => Factory\View\Helper\UrlFactory::class,
            View\Helper\HeadScript::class => Factory\View\Helper\HeadScriptFactory::class,
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

// if in dev add a opcache page
if(getenv('PHP_DEV_ENV') == 1) {
    $return['router']['routes']['cache'] = [
        'type'    => Literal::class,
        'options' => [
            'route'    => '/cache',
            'defaults' => [
                'controller' => Controller\IndexController::class,
                'action'     => 'cache',
            ],
        ],
        'may_terminate' => true,
        'child_routes' => [
            'status'=>[
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/status',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'cache-status',
                    ],
                ],
            ],
        ],
    ];
}
return $return;
