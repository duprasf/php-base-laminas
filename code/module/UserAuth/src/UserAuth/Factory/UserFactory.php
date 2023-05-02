<?php

declare(strict_types=1);

namespace UserAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use UserAuth\Model\User;
use UserAuth\Model\UserAudit;
use UserAuth\Model\UserInterface;
use UserAuth\Model\JWT;
use ActiveDirectory\Model\ActiveDirectory;

class UserFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();

        if($container->has('user-auth-password-rules') && method_exists($obj, 'setPasswordRules')) {
            $obj->setPasswordRules($container->get('user-auth-password-rules'));
        }

        $obj->setGcNotifyData([
            'api-key'=>$container->get('gc-notify-auth-system-key'),
            'confirm-email-template'=>$container->get('gc-notify-auth-confirm-email'),
            'reset-password-template'=>$container->get('gc-notify-auth-reset-password'),
        ]);

        $obj->setEventManager($container->get('EventManager'));

        if($container->has('user-pdo') && method_exists($obj, 'setParentDb')) {
            $obj->setParentDb($container->get('user-pdo'));
        }

        $obj->setUrlPlugin($container->get('router'));
        $obj->setTranslator($container->get('MvcTranslator'));

        if($container->has('user-auth-must-verify-email') && method_exists($obj, 'setDefaultValues')) {
            $obj->setDefaultValues('emailVerified', $container->get('user-auth-must-verify-email') ? 0 : 1);
        }
        if($container->has('user-auth-default-user-status') && method_exists($obj, 'setDefaultValues')) {
            $obj->setDefaultValues('status', $container->get('user-auth-default-user-status'));
        }

        $obj->setJwtObj($container->get(JWT::class));

        $this->setLdap($obj, $container);

        if(is_array($options) && isset($options['skipLoadFromSession']) && $options['skipLoadFromSession']) {
            return $obj;
        }

        if(method_exists($obj, 'loadFromSession')) {
            $obj->loadFromSession();
        }

        return $obj;
    }

    protected function setLdap($obj, ContainerInterface $container)
    {
        if(method_exists($obj, 'setLdap') && $container->has(ActiveDirectory::class)) {
            $obj->setLdap($container->get(ActiveDirectory::class));
        }
        return $obj;
    }
}
