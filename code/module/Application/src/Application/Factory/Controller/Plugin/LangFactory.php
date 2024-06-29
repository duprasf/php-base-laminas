<?php

namespace Application\Factory\Controller\Plugin;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Application\Controller\Plugin\Lang;

class LangFactory implements FactoryInterface
{
    /**
    * Get the Lang variable. By default the lang variable is sent to layout and view but there
    * is no way to have a variable in controller, this is a subtitute
    *
    * @param ContainerInterface $container
    * @param String $requestedName
    * @param array $options
    *
    * @return Lang
    */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new $requestedName($container->get('lang'));
    }
}
