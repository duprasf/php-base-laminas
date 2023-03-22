<?php
namespace Stockpile;

/**
* The path where the file-system-route can find its files is
* called 'FileSystemPage' and found in
* 'view_manager'=>[
*   'template_path_stack'=>[
*       'FileSystemPage' => "path/",
*   ],
* ]
*/
return [
    'router' => [
        'routes' => [
            'file-system-page'=>[
                'type'=>Route\FileSystemRoute::class,
                'options'=>[
                    'regex'=>'/(?P<lang>en|fr)(?P<path>/.*)?$',
                    'spec'=>'/%lang%/%path%',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'file-system-page',
                    ],
                    'constraints'=>[
                        'path'=>'^[\w\d/-]*$',
                        'lang'=>'en|fr',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                ],
            ],
            'moved-page'=>[
                'type'=>Route\MovedPageRoute::class,
                'options'=>[
                    'regex'=>'/(?P<path>.*)$',
                    'spec'=>'/%path%',
                    'defaults'=>[
                        'controller'=>Controller\IndexController::class,
                        'action'=>'moved-page',
                    ],
                ],
            ],
            'moved-pages-admin'=>[
                'type'=>'Segment',
                'options'=>[
                    'route'=>'/:locale/{moved-pages}',
                    'defaults'=>[
                        'controller'=>Controller\AdminController::class,
                        'action'=>'moved-pages-admin',
                    ],
                    'constraints'=>[
                        'locale'=>'en|fr',
                    ],
                ],
                'may_terminate' => true,
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
        'invokables' => [
        ],
    ],
    'route_manager' => [
        'factories'=>[
            Route\FileSystemRoute::class => Factory\FileSystemRouteFactory::class,
            Route\MovedPageRoute::class => Factory\MovedPageRouteFactory::class,
        ],
        'invokables'=>[],
    ],
    'view_helpers' => [
        'invokables'=> [],
        'factories' => [],
    ],
    'service_manager' => [
        'services'=>[],
        'factories' => [
            Model\MovedPage::class => Factory\MovedPageFactory::class,
        ],
        'invokables'=>[
            Model\Auth::class => Model\Auth::class,
        ],
        'shared' => [],
    ],
    'form_elements' => [
        'invokables'=>[],
    ],
    'translator' => [
        'locale' => 'en_CA',
        'translation_file_patterns' => [
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../../../language',
                'pattern'  => 'EBIC_%s.mo',
            ),
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'Stockpile'=>__DIR__ . '/../view',
        ],
        'strategies' => [],
    ],
    // Placeholder for console routes
    'console' => [
        'router' => [
            'routes' => [],
        ],
    ],
];
