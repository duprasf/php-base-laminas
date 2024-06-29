<?php

namespace Stockpile\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Laminas\Mvc\Controller\AbstractActionController;
use Void\ArrayObject;
use Stockpile\Model\MovedPage;
use UserAuth\Model\Auth;

class AdminController extends AbstractActionController
{
    private $authObj;
    public function setAuthObj(Auth $obj)
    {
        $this->authObj = $obj;
        return $this;
    }
    protected function getAuthObj()
    {
        return $this->authObj;
    }

    private $movedPageObj;
    public function setMovedPageObj(MovedPage $obj)
    {
        $this->movedPageObj = $obj;
        return $this;
    }
    protected function getMovedPageObj()
    {
        return $this->movedPageObj;
    }

    public function MovedPagesAdminAction()
    {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="My Realm"');
            header('HTTP/1.0 401 Unauthorized');
            exit('Login is required');
        } else {
            $auth = $this->getAuthObj();
            if(!$auth(
                ['admin:$2y$10$1d9mCLGOd7U.gpGSdKExieOAteD8tjqidn/VcPi3Em654PDUeEIfa'],
                $_SERVER['PHP_AUTH_USER'],
                $_SERVER['PHP_AUTH_PW']
            )) {
                exit('Login is required');
            }
        }
        $view = new ViewModel();
        $movedPages = $this->getMovedPageObj();
        $view->setVariable('movedPages', $movedPages->getMovedPages());
        return $view;
    }

    public function movedPagesRemoveAction()
    {
        $view = new JsonModel();
        $movedPages = $this->getMovedPageObj();
        $count = $movedPages->remove($_POST['movedPageId']);
        $result = array('movedPageId' => $_POST['movedPageId'], 'count' => $count);
        if(!$count) {
            $result['error'] = 'Could not delete this moved page';
        }
        $view->setVariables($result);

        return $view;
    }

    public function movedPagesAddAction()
    {
        $view = new JsonModel();
        $movedPages = $this->getMovedPageObj();
        $result = $movedPages->add($_POST['originalLocation'], $_POST['newLocation']);
        $view->setVariables($result);

        return $view;
    }

    public function movedPagesSetupAction()
    {
        exit('done');
    }
}
