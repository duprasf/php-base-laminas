<?php

declare(strict_types=1);

namespace OAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use OAuth\Model\OAuth2;

class OAuth2ClientFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();
        if(!$container->has('OAuth2ClientDBConnector')) {
            print 'You must define a config key "OAuth2ClientDBConnector" [service_manager][aliases][OAuth2ClientDBConnector=>the PDO to use]';
            exit();
        }
        $obj->setOAuth2Config($container->get('OAuth2Config'));
        $obj->setDb($container->get('OAuth2ClientDBConnector'));

        if(method_exists($obj, 'setEventManager')) {
            $obj->setEventManager($container->get('EventManager'));
        }

        return $obj;
    }
}
