<?php

declare(strict_types=1);

namespace UserAuth;

use Laminas\Router\Http\Segment;


return [
    'router' => [
        'routes' => [
            'emailLoginValidateToken' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/{email-login}/:token',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'emailLoginValidateToken',
                    ],
                ],
            ],
            /*
            'user' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/:locale/{user}',
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
            /**/
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => Factory\IndexControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            'returnUserData' => Controller\Plugin\ReturnUserData::class,
        ],
        'factories' => [
            Controller\Plugin\AuthenticateUser::class => Factory\Controller\Plugin\AuthenticateUserFactory::class,
        ],
        'aliases' => [
            'authenticateUser' => Controller\Plugin\AuthenticateUser::class,
        ],
    ],
    'service_manager' => [
        'invokables' => [
        ],
        'factories' => [
            Model\UserLogger::class => Factory\UserLoggerFactory::class,
            Model\UserAudit::class => Factory\UserAuditFactory::class,
            Model\JWT::class => Factory\JWTFactory::class,
            Listener\UserAuthListener::class => Factory\Listener\UserAuthListenerFactory::class,
            Model\User\User::class => Factory\User\UserFactory::class,

            // These are the old functions, depreciated
            Model\User::class => Factory\UserFactory::class,
            Model\EmailUser::class => Factory\UserFactory::class,
            Model\FileEmailUser::class => Factory\UserFactory::class,
            Model\LdapUser::class => Factory\UserFactory::class,
        ],
        'aliases' => [
        ],
    ],
    'listeners' => [
        Listener\UserAuthListener::class,
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
