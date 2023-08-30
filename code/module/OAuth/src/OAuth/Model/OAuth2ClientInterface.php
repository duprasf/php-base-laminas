<?php
namespace OAuth\Model;

/**
* OAuth 2 interface to be used in Lamnias framework model
*
* @author francois.dupras@hc-sc.gc.ca 2023-08-02
*/
interface OAuth2ClientInterface
{
    public function setOAuth2Config(array $config);
    public function redirect(String $method, ?string $state);
    public function getToken(String $authCode, String $configName, String $code_challenge) : ?String;
}
