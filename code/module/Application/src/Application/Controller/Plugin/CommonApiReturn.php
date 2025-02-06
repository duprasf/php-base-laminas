<?php

namespace Application\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\View\Model\JsonModel;


class CommonApiReturn extends AbstractPlugin
{
    public function __invoke(mixed $data, null|JsonModel $view=null)
    {
        if(!$view) {
            $view = new JsonModel();
        }
        $view->setVariable('data', $data);
        $view->setVariable('executionTime', microtime(true)-$GLOBALS['startTime']);

        return $view;
    }

    protected $lang;
    public function setLang($lang)
    {
        $this->lang = $lang;
        return $this;
    }
    public function getLang()
    {
        return $this->lang;
    }

    protected $url;
    public function setUrlObj($obj)
    {
        $this->url = $obj;
        return $this;
    }
    public function getUrlObj()
    {
        return $this->url;
    }
}
