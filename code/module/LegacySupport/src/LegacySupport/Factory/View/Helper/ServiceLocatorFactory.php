<?php
namespace LegacySupport\Factory\View\Helper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Application\View\Helper\GetLangSwitchUrl;

class ServiceLocatorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $sm, $requestedName, ?array $options = null)
    {
        $object = new $requestedName();
        $object->setContainerInterface($sm);
        return $object;
    }
}
