<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\EventManager\EventManagerInterface;

class CronjobController extends AbstractActionController
{
    public function cronjobAction()
    {
        $view = new JsonModel();
        $this->getEventManager()->trigger(
            'cronjob',
            $this,
            [
                'timestamp' => time(),
                'minute' => date('i'),
            ]
        );
        return $view;
    }
}
