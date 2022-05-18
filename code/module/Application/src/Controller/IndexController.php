<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use \Application\Model\Breadcrumbs;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel();
        $this->_setCommonMetadata($view);
        $view->setVariable('metadata', new \ArrayObject());
        return $view;
    }

    public function _setCommonMetadata($view)
    {
        $translator = $this->getTranslator();
        $lang = $translator->getLang();
        $view->setVariable('metadata', new \ArrayObject(array(
            "title" => $translator->translate('Home Page'),
            "description"=>$translator->translate("The home page our this web site is not set"),
            "issuedDate"=>date('Y-m-d'),
            //"extra-css"=>'/css/drug-calculator.css',
            //"extra-js"=>'/js/basic.js',
            //"cdts-version"=>'4_0_32',
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

        //$breadcrumbs = $this->getServiceLocator()->get('ViewHelperManager')->get('setBreadcrumbs');
        //$breadcrumbs($breadcrumbsItem);

    }
}
