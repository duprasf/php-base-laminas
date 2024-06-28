<?php

declare(strict_types=1);

namespace OAuth\Factory\Listener;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use OAuth\Model\OAuth2;

class OAuthSetupListenerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();
        $obj->setRouter($container->get('router'));
        $obj->setHeadScript($container->get('ViewHelperManager')->get('headScript'));
        return $obj;
    }
}
