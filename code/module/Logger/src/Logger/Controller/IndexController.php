<?php

declare(strict_types=1);

namespace Logger\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Model\Breadcrumbs;
use GcNotify\GcNotify;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $view = $this->_setCommonMetadata(new ViewModel());
        return $view;
    }

    /**
    * Set the common metadata for this project
    *
    * @param ViewModel $view
    *
    * @return ViewModel
    */
    public function _setCommonMetadata(ViewModel $view)
    {
        $translator = $this->getTranslator();
        $lang = $translator->getLang();
        $view->setVariable('metadata', new \ArrayObject(array(
            "title" => $translator->translate('Adhoc'),
            "description"=>$translator->translate("Adhoc"),
            "issuedDate"=>date('Y-m-d'),
            //"extra-css"=>'/css/stylesheet.css',
            //"extra-js"=>'/js/script.js',
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
        $view->setVariable('metadata', new \ArrayObject());

        return $view;
    }
}
