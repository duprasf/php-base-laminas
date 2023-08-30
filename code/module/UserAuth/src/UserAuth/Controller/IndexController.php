<?php
declare(strict_types=1);

namespace UserAuth\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Model\Breadcrumbs;
use GcNotify\GcNotify;
use UserAuth\Model\User;
use UserAuth\Model\UserInterface;
use UserAuth\Exception\UserConfirmException;

class IndexController extends AbstractActionController
{
    protected $gcNotify;
    public function setGcNotify(GcNotify $notify)
    {
        $this->gcNotify = $notify;
        return $this;
    }

    public function getGcNotify()
    {
        return $this->gcNotify;
    }

    protected $passwordRules=[];
    public function setPasswordRules(array $passwordRules)
    {
        $this->passwordRules = $passwordRules;
        return $this;
    }

    public function getPasswordRules()
    {
        return $this->passwordRules;
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

    protected $config = array();
    public function setConfig($key, $val)
    {
        $this->config[$key] = $val;
        return $this;
    }
    protected function getConfig($key = null, $default = null)
    {
        if($key) {
            return $this->config[$key] ?? $default;
        }
        return $this->config;
    }

    public function indexAction()
    {
        $view = $this->_setCommonMetadata(new ViewModel());
        if(!$this->getUser()->isLoggedIn()) {
            $request = $this->getRequest();
            return $this->redirect()->toRoute('user/login', ['locale'=>$this->lang()],
                ['query' => [
                    'referrer' => $request->getUriString(),
                ]]
            );
        }

        return $view;
    }

    public function loginAction()
    {
        $view = $this->_setCommonMetadata(new ViewModel());
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $this->params()->fromPost();
            if($this->getUser()->authenticate($data['email'],$data['password'])) {
                $this->flashMessenger()->addSuccessMessage($this->getTranslator()->translate('Login successful.'));
                if($this->params()->fromQuery('referrer')) {
                    return $this->redirect()->toUrl($this->params()->fromQuery('referrer'));
                }
                return $this->redirect()->toRoute('user', ['locale'=>$this->lang()]);
            } else {
                $this->flashMessenger()->addErrorMessage($this->getTranslator()->translate('There was an error login you with your credentials. Please try again.'));
                $view->setVariable('errorCount', ++$data['errorCount'] ?? 1);
            }
        }
        $view->setVariable('registrationAllowed', $this->getConfig('registrationAllowed', false));
        return $view;
    }

    public function logoutAction()
    {
        $view = $this->_setCommonMetadata(new ViewModel());
        $request = $this->getRequest();
        $this->getUser()->logout();
        $this->flashMessenger()->addSuccessMessage($this->getTranslator()->translate('Logout successful.'));
        if($this->params()->fromQuery('referrer')) {
            $this->redirect()->toUrl($this->params()->fromQuery('referrer'));
        }
        return $this->redirect()->toRoute('user', ['locale'=>$this->lang()]);
    }

    public function registerAction()
    {
        $view = $this->_setCommonMetadata(new ViewModel());

        $request = $this->getRequest();
        $passwordRules = $this->getPasswordRules();
        $view->setVariable('passwordRules', $passwordRules);

        if ($request->isPost()) {
            $errors = [];

            $translator = $this->getTranslator();

            $password = $this->params()->fromPost('password');
            $confirm = $this->params()->fromPost('confirmPassword');
            $email = $this->params()->fromPost('email');

            try {
                $isPasswordValid = $this->getUser()->validatePassword($password, $confirm);
                $errors = $this->getUser()->getLastPasswordErrors();

                if($isPasswordValid) {
                    try {
                        $results = $this->getUser()->register($email, $password, $this->getGcNotify());
                        $route = 'user/registrationError';
                        if($results == User::VERIFICATION_DONE) {
                            $route = 'user/registrationComplete';
                        }
                        if($results == User::VERIFICATION_EMAIL_SENT) {
                            $route = 'user/register/confirmationEmailSent';
                        }
                        return $this->redirect()->toRoute(
                            $route,
                            ['locale' => $this->params()->fromRoute('locale')]
                        );
                    } catch (\PDOException $e) {
                        $errors['additionalRules'] = [
                            'message'=>$translator->translate('A database error occured (was this email used to registered previously?).'),
                            'field'=>'email'
                        ];
                    } catch (\Exception $e) {
                        $errors['additionalRules'] = [
                            'message'=>$translator->translate('An unknown error occured.'),
                            'field'=>'password'
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $errors['additionalRules'] = [
                    'message'=>$translator->translate('An unknown error prevented us from registering your account. Please contact the administration team for help.'),
                    'field'=>'email'
                ];
            }

            $view->setVariable('errors', $errors);
            $view->setVariable('email', $email);
        }
        return $view;
    }

    public function confirmationEmailSentAction()
    {
        $view = $this->_setCommonMetadata(new ViewModel());
        return $view;
    }

    public function confirmEmailAction()
    {
        $view = $this->_setCommonMetadata(new ViewModel());

        $token = $this->params()->fromRoute('token');
        $usr = $this->getUser();
        try {
            $usr->handleVerifyEmailToken($token);
            return $this->redirect()->toRoute(
                'user/registrationComplete',
                ['locale' => $this->params()->fromRoute('locale')]
            );
        } catch(UserConfirmException $e) {
            $error = '';
            $translator = $this->getTranslator();
            switch($e->getCode()) {
                case UserConfirmException::CODE_TOKEN_NOT_FOUND:
                    $error = $translator->translate('This link was not found in our database');
                    break;
                case UserConfirmException::CODE_TOKEN_EXPIRED:
                    $error = $translator->translate('This link is expired.');
                    break;
                case UserConfirmException::CODE_TOKEN_ALREADY_USED:
                    $error = $translator->translate('This link was used previously.');
                    break;
                case UserConfirmException::CODE_USER_IS_BLOCKED:
                    $error = $translator->translate('This user is block.');
                    break;
                case UserConfirmException::CODE_USER_DOES_NOT_EXISTS:
                    $error = $translator->translate('This user does not exists.');
                    break;
                case UserConfirmException::CODE_EMAIL_ALREADY_CONFIRMED:
                    $error = $translator->translate('The email for the user was confirmed already.');
                    break;
                default:
                    $error = $translator->translate('Unknown error.');
                    break;
            }
            $view->setVariable('error', $error);
        }

        return $view;
    }

    public function registrationCompletedAction()
    {
        $view = $this->_setCommonMetadata(new ViewModel());
        return $view;
    }

    public function resetPasswordAction()
    {
        $view = $this->_setCommonMetadata(new ViewModel());
        $request = $this->getRequest();
        if ($request->isPost()) {
            $translator = $this->getTranslator();
            $email = $this->params()->fromPost('email');
            $success = $error = null;
            if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $token = $this->getUser()->requestResetPassword($email, $this->getGcNotify());
                if($token) {
                    $success = $translator->translate('The email was sent, please check your spam/junk folder if you did not receive it in a few minutes.');
                } else {
                    $error = $translator->translate('This email was not found in our site. Make sure it is the correct email.');
                }
            } else {
                $error = $translator->translate('Email is not of a valid format');
            }
            $view->setVariable('email', filter_var($email, FILTER_SANITIZE_EMAIL));
            $view->setVariable('postedData', true);
            $view->setVariable('success', $success);
            $view->setVariable('message', $success ?? $error);
        }
        return $view;
    }

    public function handleResetPasswordAction()
    {
        $view = $this->_setCommonMetadata(new ViewModel());
        $passwordRules = $this->getPasswordRules();
        $view->setVariable('passwordRules', $passwordRules);
        $token = $this->params()->fromRoute('token');
        $userId = $this->getUser()->getUserIdFromToken($token);
        if(!$userId) {
            return $view->setTemplate('user-auth/index/invalid-token');
        }


        $view->setVariable('token', $token);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $errors = [];

            $translator = $this->getTranslator();

            $password = $this->params()->fromPost('password');
            $confirm = $this->params()->fromPost('confirmPassword');

            try {
                $isPasswordValid = $this->getUser()->validatePassword($password, $confirm);
                $errors = $this->getUser()->getLastPasswordErrors();

                if($isPasswordValid) {
                    try {
                        $result = $this->getUser()->resetPassword($token, $password);
                        $view->setVariable('result', $result);
                    } catch (\PDOException $e) {
                        $errors['additionalRules'] = [
                            'message'=>$translator->translate('An unknown database error occured.'),
                            'field'=>'email'
                        ];
                    } catch (\Exception $e) {
                        $errors['additionalRules'] = [
                            'message'=>$translator->translate('An unknown error occured.'),
                            'field'=>'password'
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $errors['additionalRules'] = [
                    'message'=>$translator->translate('An unknown error prevented us from registering your account. Please contact the administration team for help.'),
                    'field'=>'email'
                ];
            }

            $view->setVariable('errors', $errors);
            $view->setVariable('email', $email);
        }
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
            "description"=>$translator->translate("User Authentification"),
            "issuedDate"=>date('Y-m-d'),
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
