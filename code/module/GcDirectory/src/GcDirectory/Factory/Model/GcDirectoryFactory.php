<?php
namespace GcDirectory\Factory\Model;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;

class GcDirectoryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $sm, $requestedName, ?array $options = null)
    {
        $obj = new $requestedName();

        $obj->setLang($sm->get('lang'));
        $obj->setConfig($sm->get('gc-directory-config'));

        return $obj;
    }
}