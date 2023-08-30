<?php

declare(strict_types=1);

namespace OAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use UserAuth\Model\LdapUser;
use UserAuth\Model\DbUser;
use UserAuth\Model\JWT;

class OAuth2ServerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();
        if(!$container->has('OAuth2ServerPDO')) {
            print 'You must define a config key "OAuth2ServerPDO" [service_manager][aliases][OAuth2ServerPDO=>the PDO to use]';
            exit();
        }
        $obj->setDb($container->get('OAuth2ServerPDO'));
        $obj->setJwt($container->get(JWT::class));
        $obj->setTTL('token', $container->get('OAuth2TTL'));
        $obj->setTTL('refresh', $container->get('OAuth2RefreshTTL'));
        return $obj;
    }
}
