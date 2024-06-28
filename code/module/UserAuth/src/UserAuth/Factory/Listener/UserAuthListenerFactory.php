<?php

declare(strict_types=1);

namespace UserAuth\Factory\Listener;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UserAuthListenerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();
        $obj->setHeadScript($container->get('ViewHelperManager')->get('headScript'));
        return $obj;
    }
}
