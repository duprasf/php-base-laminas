<?php

namespace UserAuth\Factory\View\Helper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use UserAuth\View\Helper\UserHelper;
use UserAuth\Model\User\User;

class UserHelperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $obj = new UserHelper();
        $obj->setUser($container->get(User::class));
        return $obj;
    }
}
