<?php

namespace Application\View\Helper;

use Laminas\View\Helper\HelperInterface;
use Laminas\View\Renderer\RendererInterface;

class SetSwitchLangUrl implements HelperInterface
{
    protected $view;
    public function setView(RendererInterface $view): self
    {
        $this->view = $view;
        return $this;
    }
    public function getView(): RendererInterface
    {
        return $this->view;
    }

    public function __invoke($url)
    {
        $this->getView()->{'switch-lang-url'}= $url;

        return $this;
    }
}
