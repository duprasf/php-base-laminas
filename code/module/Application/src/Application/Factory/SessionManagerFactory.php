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

        $session = $config['session'];

        $sessionConfig = null;
        if (isset($session['config'])) {
            $class = isset($session['config']['class'])
            ? $session['config']['class']
            : SessionConfig::class;

            $options = isset($session['config']['options'])
            ? $session['config']['options']
            : [];

            $sessionConfig = new $class();
            $sessionConfig->setOptions($options);
        }

        $sessionStorage = null;
        if (isset($session['session_storage'])) {
            $class = $session['session_storage'];
            $sessionStorage = new $class();
        }

        $sessionSaveHandler = null;
        if (isset($session['save_handler'])) {
            // class should be fetched from service manager
            // since it will require constructor arguments
            $sessionSaveHandler = $container->get($session['save_handler']);
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
