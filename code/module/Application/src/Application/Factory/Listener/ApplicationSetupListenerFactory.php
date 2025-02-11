<?php

declare(strict_types=1);

namespace Application\Factory\Listener;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\SessionManager;
use Laminas\Http\PhpEnvironment\Request;

class ApplicationSetupListenerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestName, array $options = null)
    {
        $obj = new $requestName();
        $sessionManager=$container->get('sessionManager');
        $obj->setSessionManager($sessionManager);

        $obj->setRequest($container->get(Request::class));
        $obj->setConfig($container->get('Config'));
        $obj->setDomain($container->get('domain'));
        return $obj;
    }
}
