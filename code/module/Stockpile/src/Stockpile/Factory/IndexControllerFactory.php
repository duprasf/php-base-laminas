<?php

declare(strict_types=1);

namespace Stockpile\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Void\ArrayObject;
use Stockpile\Controller\IndexController;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new IndexController();
        $config = $container->get('config');

        $obj->setTranslator($container->get('MvcTranslator'));
        $obj->setMetadata($container->get('OldHealthCanadaMetadata'));

        return $obj;
    }
}
