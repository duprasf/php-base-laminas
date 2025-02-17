<?php

namespace Application\Factory\Controller\Plugin;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class IsDevFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $obj = new $requestedName();
        $obj->setIsDev(!!getexistingenv('PHP_DEV_ENV'));
        return $obj;
    }
}
