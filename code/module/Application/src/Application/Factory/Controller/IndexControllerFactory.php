<?php

declare(strict_types=1);

namespace OAuth\Factory\Controller;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();

        if($container->has('loadBaseScript')) {
            $obj->setLoadBaseScript($container->get('loadBaseScript'));
        }
        return $obj;
    }
}
