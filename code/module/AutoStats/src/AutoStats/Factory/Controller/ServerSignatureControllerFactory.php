<?php

declare(strict_types=1);

namespace AutoStats\Factory\Controller;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ServerSignatureControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();
        $obj->setApmLitePublicKey($container->get('ApmLitePublicKey'));
        return $obj;
    }
}
