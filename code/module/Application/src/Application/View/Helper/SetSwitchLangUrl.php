<?php
namespace Application\View\Helper;

use Laminas\View\Helper\HelperInterface;
use Laminas\View\Renderer\RendererInterface;

/**
*   This is OLD. It is meant as a lecagy support for Stockpile
*/
class SetSwitchLangUrl implements HelperInterface
{
    protected $view;
    public function setView(RendererInterface $view)
    {
        $this->view = $view;
    }
    public function getView()
    {
        return $this->view;
    }

    public function __invoke($url)
    {
        $this->view->viewModel()->getCurrent()->setOption('switch-lang-url', $url);

        return $this;
    }
}
