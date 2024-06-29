<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Application\Model\Breadcrumbs;

class IndexController extends AbstractActionController
{
    private $loadBaseScript = true;
    public function setLoadBaseScript(bool $bool)
    {
        $this->loadBaseScript = $bool;
        return $this;
    }
    protected function getLoadBaseScript()
    {
        return $this->loadBaseScript;
    }

    public function indexAction()
    {
        $view = new ViewModel();
        $this->_setCommonMetadata($view);
        $view->setVariable('metadata', new \ArrayObject());
        return $view;
    }

    public function cacheAction()
    {
        $view = new ViewModel();
        $this->_setCommonMetadata($view);

        return $view;
    }

    public function cacheStatusAction()
    {
        $view = new JsonModel();
        $view->setVariables(opcache_get_status());

        return $view;
    }

    public function basescriptAction()
    {
        $response = $this->getResponse();
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/javascript');
        $response->getHeaders()->addHeaderLine('Content-Language', 'en');

        if(!$this->getLoadBaseScript()) {
            $response->setContent('');
            return $response;
        }

        $view = new ViewModel();
        $view->setTerminal(true);
        return $view;
    }

    public function _setCommonMetadata($view)
    {
        $translator = $this->getTranslator();
        $lang = $translator->getLang();
        $view->setVariable('metadata', new \ArrayObject(array(
            "title" => $translator->translate('Home Page'),
            "description" => $translator->translate("The home page our this web site is not set"),
            "issuedDate" => date('Y-m-d'),
        )));

        $view->setVariable('attribution', 'HC');

        $breadcrumbItems = new Breadcrumbs();
        if($lang == 'fr') {
            $breadcrumbItems->addBreadcrumbs([
                'http://canada.ca/'.$lang => 'Canada.ca',
                // put the default breadcrumbs for your app here (in French)
            ]);
        } else {
            $breadcrumbItems->addBreadcrumbs([
                'http://canada.ca/'.$lang => 'Canada.ca',
                // put the default breadcrumbs for your app here (in English)
            ]);
        }
        $view->setVariable('breadcrumbItems', $breadcrumbItems);
        return $view;
    }
}
