<?php

namespace Application\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;
use Application\Model\MetadataBuilder;

class MetadataBuilderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $obj = new MetadataBuilder();
        $obj->setTranslator($container->get('MvcTranslator'));
        $obj->setDefaultMetadata($container->get('default-metadata'));
        $obj->setLang($container->get('lang'));

        return $obj;
    }
}
