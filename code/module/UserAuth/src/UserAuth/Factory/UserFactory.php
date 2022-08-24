<?php

declare(strict_types=1);

namespace UserAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use UserAuth\Model\User;
use UserAuth\Model\UserAudit;
use UserAuth\Model\UserInterface;

class UserFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();

        if($container->has('user-auth-password-rules')) {
            $obj->setPasswordRules($container->get('user-auth-password-rules'));
        }

        $obj->setGcNotifyData([
            'api-key'=>$container->get('gc-notify-auth-system-key'),
            'confirm-email-template'=>$container->get('gc-notify-auth-confirm-email'),
            'reset-password-template'=>$container->get('gc-notify-auth-reset-password'),
        ]);

        $obj->setEventManager($container->get('EventManager'));
        $obj->setParentDb($container->get('user-pdo'));
        $obj->setUrlPlugin($container->get('router'));
        $obj->setTranslator($container->get('MvcTranslator'));

        if($container->has('user-auth-must-verify-email')) {
            $obj->setDefaultValues('emailVerified', $container->get('user-auth-must-verify-email') ? 0 : 1);
        }
        if($container->has('user-auth-default-user-status')) {
            $obj->setDefaultValues('status', $container->get('user-auth-default-user-status'));
        }

        if(method_exists($obj, 'loadFromSession')) {
            $obj->loadFromSession();
        }
        return $obj;
    }
}
