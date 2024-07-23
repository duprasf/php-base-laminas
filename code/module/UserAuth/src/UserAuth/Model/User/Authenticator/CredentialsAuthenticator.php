<?php

namespace UserAuth\Model\User\Authenticator;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use UserAuth\Exception\UserException;
use UserAuth\Exception\WrongPasswordException;
use UserAuth\Exception\InvalidCredentialsException;
use UserAuth\Exception\InvalidPassword;
use UserAuth\Exception\UserExistsException;
use UserAuth\Model\User\Authenticator\AuthenticatorInterface;
use UserAuth\Model\User\Authenticator\AbstractAuthenticator;

class CredentialsAuthenticator extends AbstractAuthenticator implements AuthenticatorInterface
{
    private $lastPasswordErrors = [];
    public function getLastPasswordErrors(): array
    {
        return $this->lastPasswordErrors;
    }

    /**
     * Each authenticator (Email, Credentials, token, etc.) will require different parameter to register a new user
     * @throws UserException
     * @return bool
     */
    public function register(array $data): bool
    {
        if(!$this->getCanRegister()) {
            throw new UserException("Cannot register new user");
        }
        if(!isset($data["password"]) || !$data["password"]) {
            throw new UserException("A password is mandatory");
        }
        if(!$this->isValidPassword($data["password"])) {
            throw new InvalidPassword();
        }
        if($this->getStorage()->read($data[$this->getIdField()], [$this->getIdField()])) {
            throw new UserExistsException();
        }

        if(isset($data["confirmPassword"]) && $data["password"] !== $data["confirmPassword"]) {
            throw new InvalidPassword("Password and confirmation are different");
        }
        unset($data['confirmPassword']);
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        return $this->getStorage()->insert($data);
    }

    /**
     * Each authenticator (Email, Credentials, token, etc.) will require different parameters to authenticate a user
     * @return bool
     */
    public function authenticate(string|null $email = null, string|null $password = null): array|bool
    {
        $data = $this->getStorage()->read($email);
        if(!$data) {
            throw new InvalidCredentialsException();
        }
        if(!password_verify($password, $data['password'])) {
            throw new WrongPasswordException();
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

    /**
     * Summary of isValidPassword
     * @param string $password
     * @return bool returns true if valid or false if invalid
     * @see getLastPasswordErrors
     */
    public function isValidPassword(string $password): bool
    {
        $password = trim($password);
        $passwordRules = $this->getPasswordRules();
        $translator = $this->getTranslator();

        if(isset($passwordRules['byPassSize']) && strlen($password) >= $passwordRules['byPassSize']) {
            return true;
        }

        $errors = [];
        if(isset($passwordRules['minSize']) && strlen($password) < $passwordRules['minSize']) {
            $errors['minSize'] = [
                'message' => sprintf($translator->translate('Minimum size of your password must be %d characters.', 'userAuth'), $passwordRules['minSize']),
                'field' => 'password'
            ];
        }
        if(isset($passwordRules['atLeastOneLowerCase']) && !preg_match('([a-z])', $password)) {
            $errors['atLeastOneLowerCase'] = [
                'message' => $translator->translate('Your password must containt at least one lower case letter.', 'userAuth'),
                'field' => 'password'
            ];
        }
        if(isset($passwordRules['atLeastOneUpperCase']) && !preg_match('([A-Z])', $password)) {
            $errors['atLeastOneUpperCase'] = [
                'message' => $translator->translate('Your password must containt at least one upper case letter.', 'userAuth'),
                'field' => 'password'
            ];
        }
        if(isset($passwordRules['atLeastOneNumber']) && !preg_match('([0-9])', $password)) {
            $errors['atLeastOneNumber'] = [
                'message' => $translator->translate('Your password must containt at least one number.', 'userAuth'),
                'field' => 'password'
            ];
        }
        if(isset($passwordRules['atLeastOneSpecialCharacters']) && !preg_match('(['.preg_quote($passwordRules['atLeastOneSpecialCharacters']).'])', $password)) {
            $errors['atLeastOneSpecialCharacters'] = [
                'message' => $translator->translate('Your password must containt at least one special character.', 'userAuth'),
                'field' => 'password'
            ];
        }
        if(isset($passwordRules['additionalRulesCallback'])
            && function_exists($passwordRules['additionalRulesCallback'])
            && !call_user_func($passwordRules['additionalRulesCallback'], $password)
        ) {
            $errors['additionalRulesCallback'] = [
                'message' => $translator->translate($passwordRules['additionalRulesErrorMsg'] ?? $passwordRules['additionalRules'], 'userAuth'),
                'field' => 'password'
            ];
        }
        $this->lastPasswordErrors = $errors;
        return count($errors) === 0;
    }

    private $passwordRules;
    /**
     * Define a set of rules the password must pass to be valid
     * [
     * 'byPassSize'=>(int), // if the size if longer that this value, return true directly
     * 'minSize'=>(int), // minimum size the password can be
     * 'atLeastOneLowerCase'=>bool, // the password must contain at least one lower case if true
     * 'atLeastOneUpperCase'=>bool, // the password must contain at least one upper case if true
     * 'atLeastOneNumber'=>bool, // the password must contain at least one number case if true
     * 'atLeastOneSpecialCharacters'=>string, // if populated, the password must have at least one of the characters specified
     * 'additionalRulesCallback'=>callback, // callback function that perform additional validation and must return true or false
     * ]
     *
     * @param array $passwordRules
     * @return \UserAuth\Model\User\Authenticator\CredentialsAuthenticator
     */
    public function setPasswordRules(array $passwordRules): self
    {
        $this->passwordRules = $passwordRules;
        return $this;
    }
    protected function getPasswordRules(): array
    {
        if(!$this->passwordRules) {
            $this->passwordRules = [];
        }
        return $this->passwordRules;
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

    private $tokenFieldName;
    /**
     * Set the name of the field that holds the token (required if the field is not named "token")
     * @param string $tokenFieldName
     * @return \UserAuth\Model\User\Authenticator\CredentialsAuthenticator
     */
    public function setEmailTokenFieldName(string $tokenFieldName): self
    {
        $this->tokenFieldName = $tokenFieldName;
        return $this;
    }
    protected function getEmailTokenFieldName(): string
    {
        if(!$this->tokenFieldName) {
            $this->tokenFieldName = 'token';
        }
        return $this->tokenFieldName;
    }
}
