<?php
namespace AutoStats;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack;

return [
    'router' => [
        'router_class' => TranslatorAwareTreeRouteStack::class,
        'routes' => [
            'auto-stats' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/{auto-stats}',
                    'defaults' => [
                        'controller' => Controller\AutoStatsController::class,
                    ],
                ],
            ],
            'server-signature' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/{server-signature}',
                    'defaults' => [
                        'controller' => Controller\ServerSignatureController::class,
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\ServerSignatureController::class => Factory\Controller\ServerSignatureControllerFactory::class,
            Controller\AutoStatsController::class => Factory\Controller\AutoStatsControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [
        ],
    ],
    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
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
];
