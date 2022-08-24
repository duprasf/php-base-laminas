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
                /*
                $referer = $this->getRequest()->getHeader('referer')->getUri();
                $body = '<p>Dear Administrators,<br>It seams that a page links to a moved page on the new INFRAnet. Could you please look into it.</p>

                <table border="0">
                <tr>
                    <th style="text-align:right;">Referer Page:</th>
                    <td><a href="'.$referer.'">'.$referer.'</a></td>
                </tr>
                <tr>
                    <th style="text-align:right;">Original Location:</th>
                    <td>http://'.$this->getServiceLocator()->get('domain').'/'.$this->params('originalLocation').'</td>
                </tr>
                <tr>
                    <th style="text-align:right;">New Location:</th>
                    <td><a href="http://'.$this->getServiceLocator()->get('domain').'/'.$this->params('newLocation').'">http://'.$this->getServiceLocator()->get('domain').'/'.$this->params('newLocation').'</a></td>
                </tr>
                </table>

                <br>
                <br>
                <p>Thank you,<br>
                Your always friendly INFRAnet System</p>

                <br><br>
                <p>Chers Administrateurs,<br>
                Il semble qu\'une page contient un lien vers une page sur l\'INFRAnet qui a Ã©tÃ© dÃ©placÃ©. Pouvez-vous regarder cela s\'il vous plait?</p>

                <table border="0">
                <tr>
                    <th style="text-align:right;">Page de rÃ©fÃ©rencement:</th>
                    <td><a href="'.$referer.'">'.$referer.'</a></td>
                </tr>
                <tr>
                    <th style="text-align:right;">Emplacement original:</th>
                    <td>http://'.$this->getServiceLocator()->get('domain').'/'.$this->params('originalLocation').'</td>
                </tr>
                <tr>
                    <th style="text-align:right;">Nouvel emplacement:</th>
                    <td><a href="http://'.$this->getServiceLocator()->get('domain').'/'.$this->params('newLocation').'">http://'.$this->getServiceLocator()->get('domain').'/'.$this->params('newLocation').'</a></td>
                </tr>
                </table>

                <br>
                <br>
                <p>Merci,<br>
                Votre toujours amical systÃ¨me INFRAnet</p>
                ';

                $subject = "Link to a moved page";

                $headers  = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
                $headers .= 'From: INFRAnet System <infc.webservices-servicesweb.infc@canada.ca>' . "\r\n";

                $to = "Web Services <infc.webservices-servicesweb.infc@canada.ca>";
                if(IS_LIVE) {
                    // TODO: add comms email
                    $to.=", e-Comms / Comms-e (INFC/INFC) <infc.e-comms-comms-e.infc@canada.ca>";
                }
                // DEBUG: Removed the email for now, way to many email and not enough time to fix the links.
                //mail($to, utf8_decode($subject), utf8_decode($body), $headers);
                /**/
            }
            else {
                $translator = $this->getTranslator();
                $this->flashMessenger()->addWarningMessage($translator->translate("This page was recently moved, please update your bookmark to this new address as soon as possible."));
            }
        }
        return $this->redirect()->toUrl($this->params('newLocation'))->setStatusCode(302);
    }
}
