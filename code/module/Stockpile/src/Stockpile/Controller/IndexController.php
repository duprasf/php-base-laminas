<?php
namespace Stockpile\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Void\ArrayObject;

class IndexController extends AbstractActionController
{
    private $translator;
    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
        return $this;
    }
    public function getTranslator()
    {
        return $this->translator;
    }

    public function fileSystemPageAction()
    {
        $view = new ViewModel();
        $view->setTemplate($this->params('path'));
        $view->setVariable('webPath', substr($this->params('directPath'), strlen(getcwd())));
        $view->setVariable('directPath', $this->params('directPath'));
        $view->setVariable('lang', $this->params('lang'));

        $view->setVariable('metadata', new ArrayObject());

        return $view;
    }

    public function movedPageAction()
    {
        if($this->params('originalLocation') != 'index_e.shtml' && $this->params('originalLocation') != 'index_f.shtml') {
            if($this->getRequest()->getHeader('referer')) {

            }
            else {
                $translator = $this->getTranslator();
                $this->flashMessenger()->addWarningMessage($translator->translate("This page was recently moved, please update your bookmark to this new address as soon as possible."));
            }
        }
        return $this->redirect()->toUrl($this->params('newLocation'))->setStatusCode(302);
    }
}
