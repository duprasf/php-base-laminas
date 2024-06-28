<?php
declare(strict_types=1);

namespace OAuth;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Model\User;

return [
    'router' => [
        'routes' => [
            // routes are defined in Module.php to set the low priority
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\OAuth2ServerController::class => Factory\Controller\OAuth2ServerControllerFactory::class,
            Controller\OAuth2ClientController::class => Factory\Controller\OAuth2ClientControllerFactory::class,
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
            Listener\OAuthSetupListener::class=>Factory\Listener\OAuthSetupListenerFactory::class,
            Model\OAuth2Client::class => Factory\OAuth2ClientFactory::class,
            Model\OAuth2Server::class => Factory\OAuth2ServerFactory::class,
        ],
        'aliases' => [
        ],
    ],
    'listeners'=>[
        Listener\OAuthSetupListener::class,
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
    'laminas-cli' => [
        'commands' => [
            'oauth:encode' => Command\Encode::class,
        ],
    ],
];
