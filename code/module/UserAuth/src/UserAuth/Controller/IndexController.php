<?php

declare(strict_types=1);

namespace UserAuth\Controller;

use Exception;
use Laminas\Mvc\Controller\AbstractActionController;
use UserAuth\Model\User\UserInterface;

class IndexController extends AbstractActionController
{
    public function emailLoginValidateTokenAction()
    {
        try {
            $data = $this->getUser()->validateEmail($this->params()->fromRoute('token'));

            if(isset($data['redirectToRoute'])) {
                return $this->redirect()->toRoute($data['redirectToRoute'], $data['redirectParams']??[]);
            }
            if(isset($data['redirectToUrl'])) {
                return $this->redirect()->toUrl($data['redirectToUrl']);
            }
            return $this->redirect()->toUrl('/');
        } catch (Exception $e) {
            $this->flashMessenger()->addErrorMessage($e->getMessage());
        }
    }

    public function loadJwtFromSessionAction()
    {
        $this->getUser()->loadFromSession();
        return $this->returnUserData($this->getUser());
    }

    public function pingAction()
    {
        return $this->returnUserData($this->getUser());
    }

    protected $user;
    /**
     * Set the user that will be used to validate email token
     * @param \UserAuth\Model\User\UserInterface $obj
     * @return \UserAuth\Controller\IndexController
     */
    public function setUser(UserInterface $obj): self
    {
        $this->user = $obj;
        return $this;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }
}
