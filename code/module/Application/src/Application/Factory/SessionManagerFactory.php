<?php

namespace Application\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\SessionManager;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Container;

class SessionManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');
        if (!isset($config['session'])) {
            $sessionManager = new SessionManager();
            Container::setDefaultManager($sessionManager);
            return $sessionManager;
        }

        $config = $config['session'];
        $sessionConfig = null;
        if (isset($config['config'])) {
            $class = isset($config['config']['class'])
            ? $config['config']['class']
            : SessionConfig::class;

            $options = isset($config['config']['options'])
            ? $config['config']['options']
            : [];

            $sessionConfig = new $class();
            $sessionConfig->setOptions($options);
        }

        $sessionStorage = null;
        if (isset($config['session_storage'])) {
            $class = $config['session_storage'];
            $sessionStorage = new $class();
        }

        $sessionSaveHandler = null;
        if (isset($config['save_handler'])) {
            // class should be fetched from service manager
            // since it will require constructor arguments
            $sessionSaveHandler = $container->get($config['save_handler']);
        }

        $sessionManager = new SessionManager(
            $sessionConfig,
            $sessionStorage,
            $sessionSaveHandler
        );

        Container::setDefaultManager($sessionManager);
        return $sessionManager;
    }
}
