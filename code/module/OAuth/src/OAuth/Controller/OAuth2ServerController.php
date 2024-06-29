<?php

declare(strict_types=1);

namespace OAuth\Controller;

use InvalidArgumentException;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Laminas\Session\Container;
use Application\Model\Breadcrumbs;
use UserAuth\Model\UserInterface;
use UserAuth\Exception\InvalidCredentialsException;
use OAuth\Model\OAuth2ServerInterface;

class OAuth2ServerController extends AbstractActionController
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

    protected $lang;
    public function setLang(String $lang)
    {
        $this->lang = $lang;
        return $this;
    }
    public function getLang()
    {
        return $this->lang;
    }

    protected $user;
    public function setUser(UserInterface $user)
    {
        $this->user = $user;
        return $this;
    }
    public function getUser()
    {
        return $this->user;
    }

    protected $oauthObj;
    public function setOAuth2Object(OAuth2ServerInterface $obj)
    {
        $this->oauthObj = $obj;
        return $this;
    }
    public function getOAuth2Obj()
    {
        return $this->oauthObj;
    }

    /**
    * display the "apps wants access" screen and ask for authorization
    *
    */
    public function authorizeAction()
    {
        if(!$this->enabled) {
            return $this->notFoundAction();
        }

        $view = $this->_setCommonMetadata(new ViewModel());

        $session = new Container('oauth_authorize');
        if($this->params()->fromQuery()) {
            $session['params'] = $this->params()->fromQuery();
        }

        $client = $this->getOAuth2Obj()->verifyClient(
            $session['params']['client_id'],
            $session['params']['redirect_uri'],
            json_decode($session['params']['scope'] ?? [], true),
            $this->lang
        );

        $view->setVariables($client);

        $session['params']['clientId'] = $client['clientId'];

        $returnUri = $session['params']['redirect_uri'];
        $returnUri = $returnUri . (strpos($returnUri, '?') === false ? '?' : '&'). 'error=access_denied&state='.urlencode($session['params']['state'] ?? '');
        $view->setVariable('returnUrl', $returnUri);
        $view->setVariable('username', $session['username'] ?? '');
        $session['username'] = '';
        $view->setVariable('domain', $this->params()->fromRoute('domain'));
        $view->setVariable('tld', $this->params()->fromRoute('tld'));
        return $view;
    }

    /**
    * Authorization was granted, now verify user identity with JWT or login credentials
    *
    */
    public function authorizeLoginAction()
    {
        if(!$this->enabled) {
            return $this->notFoundAction();
        }

        $session = new Container('oauth_authorize');
        $params = $session['params'];
        $jwt = $this->params()->fromPost('jwt');
        $user = $this->getUser();

        try {
            if($jwt) {
                $user->loadFromJwt($jwt);
            } else {

                if(!$this->params()->fromPost('username') || !$this->params()->fromPost('password')) {
                    throw new InvalidCredentialsException('Missing arguments');
                }
                $user->login(
                    $this->params()->fromPost('username'),
                    $this->params()->fromPost('password')
                );
            }
        } catch(InvalidCredentialsException $e) {
            $session['username'] = $this->params()->fromPost('username');
            $this->flashMessenger()->addErrorMessage('Invalid credentials');
            return $this->redirect()->toRoute('oauth-server/authorize');
        }

        $code = $this->getOAuth2Obj()
            ->getAuthorizationCode(
                $params['clientId'],
                $user->getUserId(),
                $params['redirect_uri'],
                json_decode($params['scope'], true),
                $params['code_challenge'],
                $params['code_challenge_method'],
                $user->getDataForJWT()
            )
        ;

        $redirectData = [
            'state' => $params['state'],
            'code' => $code,
        ];

        return $this->redirect()->toUrl(
            $params['redirect_uri']
            . (strpos($params['redirect_uri'], '?') === false ? '?' : '&') .
            http_build_query($redirectData)
        );
    }

    /**
    * Once the authorization code was sent, the app server should ask to exchange the
    * code for a token (JWT)
    *
    */
    public function tokenAction()
    {

        if(!$this->enabled) {
            return $this->notFoundAction();
        }
        $data = $this->params()->fromPost();
        $required = array_flip(["grant_type","code","redirect_uri","code_verifier","client_id","client_secret"]);

        if(count(array_intersect_key($data, $required)) != count($required) || $data['grant_type'] != "authorization_code") {
            $this->response->setStatusCode(400);
            return new JsonModel(['content' => 'Bad Request']);
        }

        $token = [];
        try {
            unset($data["grant_type"]);
            $token = $this->getOAuth2Obj()->getToken(...$data);

        } catch(AuthorizationExpired $e) {
            $this->response->setStatusCode(408);
            return new JsonModel(['content' => 'Request Timeout']);
        } catch(OAuthException $e) {
            $this->response->setStatusCode(500);
            return new JsonModel(['content' => 'Internal Server Error']);
        } catch(\Exception $e) {
            $this->response->setStatusCode(500);
            return new JsonModel(['content' => 'Internal Server Error']);
        }
        $view = new JsonModel($token);
        return $view;
    }

    public function revokeAction()
    {
        if(!$this->enabled) {
            return $this->notFoundAction();
        }
        $view = $this->_setCommonMetadata(new ViewModel());

        return $view;
    }

    public function resourceAction()
    {
        if(!$this->enabled) {
            return $this->notFoundAction();
        }
        $view = $this->_setCommonMetadata(new ViewModel());

        return $view;
    }

    public function receiveCodeAction()
    {
        if(!$this->enabled) {
            return $this->notFoundAction();
        }
        $view = $this->_setCommonMetadata(new ViewModel());

        return $view;
    }

    public function adminAction()
    {
        if(!$this->enabled) {
            return $this->notFoundAction();
        }
        $view = $this->_setCommonMetadata(new ViewModel());
        if(!$this->getRequest()->isPost()) {
            return $view;
        }
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
            'showShare' => false,
            'showFeedback' => false,
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
