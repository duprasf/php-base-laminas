<?php

declare(strict_types=1);

namespace UserAuth\Factory\User;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use UserAuth\Model\JWT;
use UserAuth\Model\User\User;

class UserFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new User();

        if($container->has('user-password-rules') && method_exists($obj, 'setPasswordRules')) {
            $obj->setPasswordRules($container->get('user-password-rules'));
        }

        if(method_exists($obj, 'setTranslator')) {
            $obj->setTranslator($container->get('MvcTranslator'));
        }

        $obj->setEventManager($container->get('EventManager'));
        $obj->setJwtObj($container->get(JWT::class));

        return $obj;
    }
}
