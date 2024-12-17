<?php

namespace UserAuth\Model\User\Authenticator;

use Void\UUID;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\View\Helper\Url as UrlHelper;
use GcNotify\GcNotify;
use Application\Interface\EmailerInterface;
use UserAuth\Exception\UserException;
use UserAuth\Exception\UserExistsException;
use UserAuth\Exception\InvalidCredentialsException;
use UserAuth\Model\User\Authenticator\AuthenticatorInterface;
use UserAuth\Model\User\Authenticator\AbstractAuthenticator;

class EmailAuthenticator extends AbstractAuthenticator implements AuthenticatorInterface
{
    /**
     * Each authenticator (Email, Credentials, token, etc.) will require different parameter to register a new user
     * Create a new user, if allowed, by inserting the data in the storage
     * @throws UserException
     * @return bool
     */
    public function register(array $data): bool
    {
        if(!$this->getCanRegister()) {
            throw new UserException("Cannot register new user");
        }
        if($this->getStorage()->read($data[$this->getIdField()], [$this->getIdField()])) {
            throw new UserExistsException();
        }
        return $this->getStorage()->insert($data);
    }

    /**
     * Each authenticator (Email, Credentials, token, etc.) will require different parameter to authenticate a user
     * Authenticate in EmailAuthenticator means validating the user exists, setting the token and sending the email.
     * @param string|null $email
     * @param string|null $redirectToRoute the route where the user will be redirected after validating the token
     * @throws \UserAuth\Exception\UserException
     * @return bool
     */
    public function authenticate(string|null $email = null, string|null $redirectToRoute = null, string|null $token = null): array|bool
    {
        if($token) {
            return $this->validate($token);
        }
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new UserExistsException('Trying to authenticate without an email');
        }
        if(!$redirectToRoute && $this->verificationRouteName) {
            throw new UserException('You MUST define a redirectToRoute parameter if not using a custom verificationRouteName');
        }

        $token = $this->getStorage()->findUniqueValue($this->getEmailTokenFieldName(), [UUID::class, 'v4']);
        if(!$token) {
            return false;
        }

        $this->getStorage()->update($email, [
            'token' => $token,
            'redirectToRoute' => $redirectToRoute,
            'expiryTimestamp' => date('Y-m-d H:i:s', time() + $this->getTimeToLive()),
        ]);

        $notify = $this->getGcNotify();
        $routeName = $this->getVerificationRouteName();
        $url = $this->getUrlHelper();
        $return = $notify->sendAuthenticationEmail(
            $email,
            'login-'.$this->getLang(),
            $url(
                $routeName,
                ['locale' => $this->getLang(),'token' => $token,],
                ['force_canonical' => true,]
            )
        );

        if(!$return) {
            throw new UserException($notify->lastPage);
        }

        return true;
    }

    /**
     * Validating the user by using the token send by the authenticate function
     * @return bool
     */
    public function validateToken(string|null $token = null): array
    {
        $data = $this->getStorage()->findByToken($token);
        if(!$data) {
            throw new InvalidCredentialsException("This token is invalid");
        }
        //TODO: this might be removed at some point. It prevent removal of token so
        // someone that test does not have to regenerate a new token each time
        if(getExistingEnv('PHP_DEV_ENV')) {
            return $data;
        }
        $this->getStorage()->update($data['email'], ['token' => null, 'expiryTimestamp' => null]);
        if(strtotime($data['expiryTimestamp']) <= time()) {
            throw new InvalidCredentialsException("This token is expired");
        }

        return $data;
    }

    /**
     * If you need to do something in the storage for the logout, this is the time to do it
     * By default, this only return true
     * @return bool
     */
    public function logout(): bool
    {
        return true;
    }

    protected $ttl = 3600;
    /**
     * Set the time (in sec) the token will be valid. If none is specified, 1 hour will be used
     * @param int $ttl
     * @return \UserAuth\Model\User\Authenticator\EmailAuthenticator
     */
    public function setTimeToLive(int $ttl): self
    {
        $this->ttl = $ttl;
        return $this;
    }
    protected function getTimeToLive(): int
    {
        return $this->ttl;
    }

    private $verificationRouteName;
    /**
     * Set the name of the route that will be used to verify the email token.
     * Use default 'emailLoginValidateToken' if not set.
     * @param string $name
     * @return \UserAuth\Model\User\Authenticator\EmailAuthenticator
     */
    public function setVerificationRouteName(string $name): self
    {
        $this->verificationRouteName = $name;
        return $this;
    }
    protected function getVerificationRouteName(): string
    {
        return $this->verificationRouteName ?? 'emailLoginValidateToken';
    }

    private $urlHelper = null;
    /**
     * Set the URL view helper to create URL in emails
     * @param \Laminas\View\Helper\Url $obj
     * @return \UserAuth\Model\User\Authenticator\EmailAuthenticator
     */
    public function setUrlHelper(UrlHelper $obj): self
    {
        $this->urlHelper = $obj;
        return $this;
    }
    protected function getUrlHelper(): UrlHelper
    {
        return $this->urlHelper;
    }

    private $translator = null;
    /**
     * Set the MvcTranslator, this is used when generating the links in the emails
     * @param \Laminas\Mvc\I18n\Translator $mvcTranslator
     * @return \UserAuth\Model\User\Authenticator\CredentialsAuthenticator
     */
    public function setTranslator(MvcTranslator $mvcTranslator): self
    {
        $this->translator = $mvcTranslator;
        return $this;
    }
    protected function getTranslator()
    {
        return $this->translator;
    }

    private $lang;
    public function setLang(string $lang): self
    {
        $this->lang = $lang;
        return $this;
    }
    protected function getLang(): string
    {
        if(!$this->lang) {
            throw new UserException('Language was not set');
        }
        return $this->lang;
    }

    private $tokenFieldName = 'token';
    public function setEmailTokenFieldName(string $tokenFieldName): self
    {
        $this->tokenFieldName = $tokenFieldName;
        return $this;
    }
    protected function getEmailTokenFieldName(): string
    {
        return $this->tokenFieldName;
    }

    private $emailer;
    public function setEmailer(EmailerInterface $emailer): self
    {
        $this->emailer = $emailer;
        return $this;
    }
    protected function getEmailer(): EmailerInterface
    {
        return $this->emailer;
    }

    public function setGcNotify(GcNotify $obj): self
    {
        return $this->setEmailer($obj);
    }
    protected function getGcNotify(): GcNotify
    {
        return $this->getEmailer();
    }
}
