<?php

declare(strict_types=1);

namespace UserAuth;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Model\User;

return [
    'router' => [
        'routes' => [
            'user' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/:locale/{user}',
                    'defaults' => [
                    ],
                    'constraints'=>[
                        'locale'=>'en|fr',
                    ],
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes'=>[
                    'login' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/{login}',
                            'defaults' => [
                            ],
                            'defaults' => [
                                'action'     => 'login',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'=>[
                        ],
                    ],
                    'logout' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/{logout}',
                            'defaults' => [
                            ],
                            'defaults' => [
                                'action'     => 'logout',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'=>[
                        ],
                    ],
                    'register' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/{register}',
                            'defaults' => [
                            ],
                            'defaults' => [
                                'action'     => 'register',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'=>[
                            'confirmationEmailSent' => [
                                'type'    => Segment::class,
                                'options' => [
                                    'route'    => '/{confirmation-email-sent}',
                                    'defaults' => [
                                        'action'     => 'confirmationEmailSent',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes'=>[
                                ],
                            ],
                        ],
                    ],
                    'registrationComplete' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/{registration-completed}',
                            'defaults' => [
                                'action'     => 'registrationCompleted',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'=>[
                        ],
                    ],
                    'confirm-email' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/{confirm-email}/:token',
                            'defaults' => [
                                'action'     => 'confirmEmail',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'=>[
                        ],
                    ],
                    'reset-password' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/{reset-password}',
                            'defaults' => [
                                'action'     => 'resetPassword',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'=>[
                            'handle' => [
                                'type'    => Segment::class,
                                'options' => [
                                    'route'    => '/:token',
                                    'defaults' => [
                                        'action'     => 'handleResetPassword',
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes'=>[
                                ],
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
        ],
    ],
    'controller_plugins' => [
        'factories' => [
        ],
        'aliases' => [
        ],
    ],
    'service_manager' => [
        'invokables'=>[
        ],
        'factories' => [
            Model\UserLogger::class => Factory\UserLoggerFactory::class,
            Model\UserAudit::class => Factory\UserAuditFactory::class,
            Model\User::class => Factory\UserFactory::class,
            Model\JWT::class => Factory\JWTFactory::class,
        ],
        'aliases' => [
        ],
    ],
    'view_helpers' => [
        'invokables' => [
        ],
        'factories' => [
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __NAMESPACE__ => __DIR__ . '/../view',
        ],
    ],
];
