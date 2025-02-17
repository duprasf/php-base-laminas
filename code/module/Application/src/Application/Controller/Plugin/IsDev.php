<?php

namespace Application\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class IsDev extends AbstractPlugin
{
    public function __invoke(): bool
    {
        return $this->isDev;
    }

    private $isDev=false;
    public function setIsDev(bool $val)
    {
        $this->isDev = $val;
        return $this;
    }
}
