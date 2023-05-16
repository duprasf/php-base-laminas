<?php
namespace Stockpile;

return [
    'router' => [
        'routes' => [
            'moved-pages-admin'=>[
                'child_routes' => [
                    'setup'=>[
                        'type'=>'literal',
                        'options'=>[
                            'route'=>'/setup',
                            'defaults'=>[
                                'action'=>'moved-pages-setup',
                            ],
                        ],
                    ],
                    'remove'=>[
                        'type'=>'literal',
                        'options'=>[
                            'route'=>'/remove',
                            'defaults'=>[
                                'action'=>'moved-pages-remove',
                            ],
                        ],
                    ],
                    'add'=>[
                        'type'=>'literal',
                        'options'=>[
                            'route'=>'/add',
                            'defaults'=>[
                                'action'=>'moved-pages-add',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => Factory\IndexControllerFactory::class,
            Controller\AdminController::class => Factory\AdminControllerFactory::class,
        ],
    ],
    'route_manager' => [
        'factories'=>[
            Route\FileSystemRoute::class => Factory\FileSystemRouteFactory::class,
            Route\MovedPageRoute::class => Factory\MovedPageRouteFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            Model\MovedPage::class => Factory\MovedPageFactory::class,
        ],
        'invokables'=>[
            Model\Auth::class => Model\Auth::class,
        ],
    ],
    'translator' => [
        'locale' => 'en_CA',
        'translation_file_patterns' => [
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'Stockpile'=>__DIR__ . '/../view',
        ],
    ],
];
