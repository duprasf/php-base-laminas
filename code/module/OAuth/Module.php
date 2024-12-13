<?php

namespace OAuth;

use Laminas\Stdlib\ArrayUtils;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;

class Module implements ConfigProviderInterface
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
        return [
            'Laminas\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php',
            ],
            'Laminas\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }
}
