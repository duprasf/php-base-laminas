<?php
declare(strict_types=1);

namespace ActiveDirectory\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use ActiveDirectory\Controller\IndexController;
use ActiveDirectory\Model\ActiveDirectory;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new IndexController();
        return $obj;
    }
}
