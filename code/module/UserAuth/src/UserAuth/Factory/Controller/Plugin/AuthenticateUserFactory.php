<?php
/**
 * Application Plugin Factory
 * To create Application Plugin by injecting config array
 */

namespace UserAuth\Factory\Controller\Plugin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use UserAuth\Model\JWT;
use UserAuth\Model\User\User;

class AuthenticateUserFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $obj = new $requestedName();
        $obj->setJwtObj($container->get(JWT::class));
        $obj->setUser($container->get(User::class));

        return $obj;
    }
}
