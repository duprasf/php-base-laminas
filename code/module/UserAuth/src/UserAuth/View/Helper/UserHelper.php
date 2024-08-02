<?php

namespace UserAuth\View\Helper;

use Laminas\View\Helper\HelperInterface;
use Laminas\View\Renderer\RendererInterface;
use UserAuth\Model\User\UserInterface;

/**
* Generate the WET HTML for the breadcrumbs
*/
class UserHelper implements HelperInterface
{
    public function __invoke()
    {
        return $this->user;
    }

    protected $user;
    public function setUser(UserInterface $obj): self
    {
        $this->user = $obj;
        return $this;
    }
    public function getUser(): UserInterface
    {
        return $this->user;
    }

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
}
