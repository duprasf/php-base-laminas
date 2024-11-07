<?php

namespace Application\Trait;

use Laminas\EventManager\EventManagerInterface;

trait EventManagerAwareTrait
{
    private $eventManager;
    public function setEventManager(EventManagerInterface $obj): self
    {
        $this->eventManager = $obj;
        return $this;
    }
    protected function getEventManager(): EventManagerInterface
    {
        return $this->eventManager;
    }
}
