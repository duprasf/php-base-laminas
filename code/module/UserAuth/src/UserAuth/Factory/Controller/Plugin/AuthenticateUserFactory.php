<?php
/**
 * Application Plugin Factory
 * To create Application Plugin by injecting config array
 */

namespace UserAuth\Factory\Controller\Plugin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Mvc\I18n\Translator;
use UserAuth\Model\JWT;
use UserAuth\Model\EmailUser;


class AuthenticateUserFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $obj = new $requestedName();
        $obj->setJwtObj($container->get(JWT::class));
        $obj->setUser($container->get(EmailUser::class));

        return $obj;
    }
}
