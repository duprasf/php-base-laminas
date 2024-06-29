<?php

declare(strict_types=1);

namespace Application;

use Laminas\Stdlib\ArrayUtils;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\ModuleManager\ModuleManager;
use GcNotify\GcNotify;

/**
* Base configuration class for the Application module. This is the namespace for generic features
*/
class Module
{
    /**
    * @ignore this is a default method for Lamnias, no need to be in documentation
    */
    public function getConfig(): array
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
                    "CurlWrapper" => __DIR__ . '/src/CurlWrapper',
                    "GcNotify" => __DIR__ . '/src/GcNotify',
                ],
            ],
        ];
    }
}
