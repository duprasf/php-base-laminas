<?php

namespace Application\View\Helper;

use Laminas\View\Helper\HelperInterface;
use Laminas\View\Renderer\RendererInterface;

class SideMenu implements HelperInterface
{
    public const LOCATION_LEFT = 'left';
    public const LOCATION_RIGHT = 'right';

    protected $view;
    public function setView(RendererInterface $view)
    {
        $this->view = $view;
    }
    public function getView()
    {
        return $this->view;
    }

    public function __invoke($content, $location = self::LOCATION_LEFT)
    {
        if($location != self::LOCATION_LEFT && $location != self::LOCATION_RIGHT) {
            $location = self::LOCATION_LEFT;
        }
        $this->view->viewModel()->getCurrent()->setOption('side-menu', $content);
        $this->view->viewModel()->getCurrent()->setOption('side-menu-location', $location);

        return $this;
    }
}
