<?php

declare(strict_types=1);

namespace UserAuth\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use UserAuth\Controller\IndexController;
use UserAuth\Model\User\User;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new IndexController();
        $obj->setUser($container->get(User::class));
        return $obj;
    }
}
