<?php

declare(strict_types=1);

namespace Application\Factory\Controller;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();
        $array = [
            'EventManager'=>'setEventManager',
        ];
        foreach($array as $serviceName=>$method) {
            if(method_exists($obj, $method) && $container->has($serviceName)) {
                call_user_func([$obj, $method], $container->get($serviceName));
            }
        }
        return $obj;
    }
}
