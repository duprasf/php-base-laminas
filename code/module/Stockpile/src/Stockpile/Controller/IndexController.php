<?php

namespace Stockpile\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Void\ArrayObject;
use Application\Model\Metadata;
use Application\Model\Breadcrumbs;

class IndexController extends AbstractActionController
{
    private $metadata;
    public function setMetadata(ArrayObject|Metadata $obj)
    {
        $this->metadata = $obj;
        return $this;
    }
    protected function getMetadata()
    {
        return $this->metadata;
    }

    public function fileSystemPageAction()
    {
        $view = new ViewModel();
        $view->setTemplate($this->params('path'));
        $view->setVariable('webPath', substr($this->params('directPath'), strlen(getcwd())));
        $view->setVariable('directPath', $this->params('directPath'));
        $view->setVariable('lang', $this->params('lang'));

        $view->setVariable('metadata', $this->getMetadata());
        $view->setVariable('breadcrumbItems', new Breadcrumbs([
            [
                'href'=>'http://canada.ca/'.$this->getTranslator()->getLang(),
                'title' => 'Canada.ca'
            ],
        ]));
        $view->setVariable('page', $this->getMetadata());

        return $view;
    }

    public function movedPageAction()
    {
        if($this->params('originalLocation') != 'index_e.shtml' && $this->params('originalLocation') != 'index_f.shtml') {
            if($this->getRequest()->getHeader('referer')) {

            } else {
                $translator = $this->getTranslator();
                $this->flashMessenger()->addWarningMessage($translator->translate("This page was recently moved, please update your bookmark to this new address as soon as possible."));
            }
        }
        return $this->redirect()->toUrl($this->params('newLocation'))->setStatusCode(302);
    }
}
