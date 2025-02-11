<?php

declare(strict_types=1);

namespace UserAuth;

use Laminas\Router\Http\Segment;
use Laminas\Session\Storage\SessionArrayStorage;

return [
    'router' => [
        'routes' => [
            'emailLoginValidateToken' => [
                'type'    => Segment::class,
                'priority' => 100,
                'options' => [
                    'route'    => '/{email-validation}/:token',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'emailLoginValidateToken',
                    ],
                ],
            ],
            'loadJwtFromSession' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/{load-jwt-from-session}',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'loadJwtFromSession',
                    ],
                ],
            ],
            'ping' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/{ping}',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'ping',
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
            Model\User\Authenticator\EmailAuthenticator::class => Factory\User\Authenticator\EmailAuthenticatorFactory::class,
        ],
        'aliases' => [
        ],
    ],
    'listeners' => [
        Listener\UserAuthListener::class,
    ],
    'view_helpers' => [
        'aliases' => [
            'user'=>View\Helper\UserHelper::class,
        ],
        'factories' => [
            View\Helper\UserHelper::class=>Factory\View\Helper\UserHelperFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __NAMESPACE__ => __DIR__ . '/../view',
        ],
    ],
    'session' => [
        'config' => [
            'options'=> [
                'use_cookies' => true,
                'cookie_domain'=>getenv('PHP_SESSION_DOMAIN'),
                'name' => "sessionName",
                'remember_me_seconds' => getenv('PHP_SESSION_TIME') ?: (ini_get('session.gc_maxlifetime') ?: 86400),
            ],
        ],
    ],
    'session_storage' => [
        'type' => SessionArrayStorage::class,
    ],
];
