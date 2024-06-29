<?php

namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MetadataFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $obj = new $requestedName();
        $obj->setDefaultMetadata($container->get('default-metadata'));
        $obj->init();
        return $obj;
    }
}
