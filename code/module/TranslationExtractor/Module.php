<?php

namespace TranslationExtractor;

use Laminas\Stdlib\ArrayUtils;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\EventManager\Event;

/**
* @ignore basic module class for laminas, no need to include it in the documentation
*/
class Module
{
    /**
    * @ignore this is a default method for Lamnias, no need to be in documentation
    */
    public function getConfig()
    {
        $config = include __DIR__ . '/config/module.config.php';
        foreach(glob(__DIR__ . '/config/autoload/{,*.}{global,local}.php', GLOB_BRACE) as $file) {
            if(is_readable($file)) {
                $config = ArrayUtils::merge($config, include($file));
            }
        }
        return $config;
    }

    /**
    * @ignore this is a default method for Lamnias, no need to be in documentation
    */
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
