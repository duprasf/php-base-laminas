<?php

declare(strict_types=1);

namespace OAuth\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Application\Model\Breadcrumbs;
use GcNotify\GcNotify;
use UserAuth\Model\UserInterface;
use OAuth\Model\OAuth2ClientInterface;
use OAuth\Exception\MethodNotFound;

class OAuth2ClientController extends AbstractActionController
{
    protected $enabled;
    public function setEnabled(bool $bool)
    {
        $this->enabled = $bool;
        return $this;
    }
    public function getEnabled(): bool
    {
        return !!$this->enabled;
    }

    protected $oauthObj;
    public function setOAuth2Client(OAuth2ClientInterface $obj)
    {
        $this->oauthObj = $obj;
        return $this;
    }

    public function getOAuth2Client()
    {
        return $this->oauthObj;
    }

    protected $userObj;
    public function setUser(UserInterface $user)
    {
        $this->userObj = $user;
        return $this;
    }
    public function getUser()
    {
        return $this->userObj;
    }

    protected $defaultController;
    public function setDefaultController(String $controller, String $action)
    {
        $this->defaultController = [$controller, $action];
        return $this;
    }
    public function getDefaultController()
    {
        return $this->defaultController;
    }

    public function jsAction()
    {
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/javascript');
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Language', 'en');
        if(!$this->enabled) {
            $this->getResponse()->setContent('');
            return $this->getResponse();
        }

        $view = new ViewModel();
        $view->setTerminal(true);

        return $view;
    }

    public function indexAction()
    {
        if(!$this->enabled) {
            return $this->notFoundAction();
        }
        try {
            return $this->redirect()->toURL(
                $this->getOAuth2Client()->redirect(
                    $this->params()->fromRoute('method'),
                    $this->params()->fromQuery('state')
                )
            );
        } catch(MethodNotFound $e) {
            return $this->notFoundAction();
        }
    }

    public function returnAction()
    {
        if(!$this->enabled) {
            return $this->notFoundAction();
        }
        $view = $this->_setCommonMetadata(new ViewModel());

        $state = $this->params()->fromQuery('state');
        $code = $this->params()->fromQuery('code');
        $error = $this->params()->fromQuery('error');
        if($error || !$code) {
            if(filter_var($state, FILTER_VALIDATE_URL)) {
                return $this->redirect()->toUrl($state.(strpos($state, '?') === false ? '?' : '&'). 'error='.$error);
            }

            $json = json_decode($state, true);
            if(json_last_error() === JSON_ERROR_NONE && isset($json['controller'])) {
                $params = $json['params'] ?? [];
                $params['error'] = $error;
                return $this->forward()->dispatch($json['controller'], $params);
            }
            $view->setTemplate('o-auth/o-auth2-client/denied.phtml');
            return $view;
        }

        $token = $this->getOAuth2Client()->getToken($code, '', '');
        // if there was no token returned display an error page
        if(!$token) {
            $view->setTemplate('o-auth/o-auth2-client/error.phtml');
            return $view;
        }

        // if the state is an URL, redirect to that URL with the token
        if(filter_var($state, FILTER_VALIDATE_URL)) {
            return $this->redirect()->toUrl($state.(strpos($state, '?') === false ? '?' : '&'). 'token='.$token);
        }

        // the state should be a JSON if not a URL...
        $json = json_decode($state, true);

        // if you have a controller and action, dispatch the request
        if(is_array($json) && isset($json['controller']) && isset($json['action'])) {
            $params = $json;
            $params['token'] = $token;
            return $this->forward()->dispatch($json['controller'], $params);
        }

        if($this->getDefaultController()) {
            $params = $json;
            $params['action'] = $this->getDefaultController()[1];
            $params['token'] = $token;
            return $this
                ->forward()
                ->dispatch(
                    $this->getDefaultController()[0],
                    $params
                )
            ;
        }

        $view->setVariable('token', $token);
        return $view;
    }

    /**
    * Set the common metadata for this project
    *
    * @param ViewModel $view
    *
    * @return ViewModel
    */
    protected function _setCommonMetadata(ViewModel $view)
    {
        $translator = $this->getTranslator();
        $lang = $translator->getLang();
        $metadata = new \ArrayObject([
            "title" => $translator->translate('User Authentification'),
            "description" => $translator->translate("User Authentification"),
            "issuedDate" => date('Y-m-d'),
            //"extra-css"=>'/css/stylesheet.css',
            //"extra-js"=>'/js/script.js',
        ]);
        $view->setVariable('metadata', $metadata);

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
