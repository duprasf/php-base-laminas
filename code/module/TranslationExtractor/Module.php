<?php
namespace TranslationExtractor;

use Laminas\ModuleManager\Feature\AutoloaderProviderInterface;
use Laminas\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Laminas\Console\Adapter\AdapterInterface as Console;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\EventManager\Event;

class Module implements ConsoleBannerProviderInterface
{
    /**
    * This method is defined in ConsoleBannerProviderInterface
    */
    public function getConsoleBanner(Console $console)
    {
        return "Translation Extractor 0.1".PHP_EOL;
    }

    public function getConsoleUsage(Console $console)
    {
        return array(
            'extract-translation [--all|-a] <scanFolder> [--form=] <output>' => 'extract translation string into a .po file',
        );
    }

    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}