<?php

namespace LegacySupport\View\Helper;

use Interop\Container\ContainerInterface;
use Laminas\View\Helper\HelperInterface;
use Laminas\View\Renderer\RendererInterface;

/**
* For legacy support only!
* Allow directly access the service locator
* Please find a better way for your class that to use this helper
*/
class ServiceLocator implements HelperInterface
{
    protected $view;
    public function setView(RendererInterface $view)
    {
        $this->view = $view;
        return $this;
    }
    public function getView()
    {
        return $this->view;
    }

    protected $container;
    public function setContainerInterface(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }


    public function __invoke()
    {
        return $this->container;
    }
}
