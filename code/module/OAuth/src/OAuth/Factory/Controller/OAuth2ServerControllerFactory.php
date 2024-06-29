<?php

declare(strict_types=1);

namespace OAuth\Factory\Controller;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use OAuth\Controller\OAuth2ServerController;
use OAuth\Model\OAuth2Server;

class OAuth2ServerControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {

        $obj = new $requestName();
        if(!$container->has('OAuth2Enabled') || $container->get('OAuth2Enabled') == false) {
            $obj->setEnabled(false);
            return $obj;
        }

        $obj->setEnabled($container->get('OAuth2Enabled'));
        $obj->setLang($container->get('lang'));
        $obj->setOAuth2Object($container->get(OAuth2Server::class));
        if(!$container->has('OAuth2ServerUser')) {
            print "You must provide a 'OAuth2ServerUser' config key to use OAuth2 [service_manager][aliases][OAuth2ServerUser=>YourUserClass]";
            exit();
        }
        $obj->setUser($container->get('OAuth2ServerUser'));

        return $obj;
    }
}
