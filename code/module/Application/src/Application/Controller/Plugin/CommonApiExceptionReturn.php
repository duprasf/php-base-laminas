<?php

namespace Application\Controller\Plugin;

use Exception;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\View\Model\JsonModel;

class CommonApiExceptionReturn extends AbstractPlugin
{
    public function __invoke(Exception $e, null|JsonModel $view=null)
    {
        if(!$view) {
            $view = new JsonModel();
        }
        $view->setVariable(
            'error',
            getenv("PHP_DEV_ENV")
                ? $e->getMessage() .' ('.$e->getFile().':'.$e->getLine().')'
                : 'Unknown error'
        );

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
