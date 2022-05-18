<?php
namespace Application\Factory;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;
use \Application\Model\MetadataBuilder;

class MetadataBuilderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $servicelocator, $requestedName, ?array $options = null)
    {
        return $this->createService($servicelocator);
    }

    /**
    * Create service
    *
    * @param ServiceLocatorInterface $serviceLocator
    *
    * @return GcNotify
    */
    public function createService(ServiceLocatorInterface $sm)
    {
        $obj = new MetadataBuilder();
        $obj->setTranslator($sm->get('MvcTranslator'));
        $obj->setDefaultMetadata($sm->get('default-metadata'));
        $obj->setLang($sm->get('lang'));

        return $obj;
    }
}