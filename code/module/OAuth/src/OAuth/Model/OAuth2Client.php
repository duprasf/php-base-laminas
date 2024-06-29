<?php

namespace OAuth\Model;

use PDO;
use Laminas\Session\Container;
use UserAuth\Model\JWT;
use OAuth\Exception\MissingMandatoryValue;
use OAuth\Exception\MethodNotFound;

/**
* OAuth 2 class to be used in Lamnias framework model
*
* @author francois.dupras@hc-sc.gc.ca 2023-08-02
*/
class OAuth2Client implements OAuth2ClientInterface
{
    private $config;
    public function setOAuth2Config(array $config)
    {
        $this->config = $config;
        return $this;
    }
    protected function getOAuth2Config()
    {
        return $this->config;
    }

    private $db;
    public function setDb(PDO $obj)
    {
        $this->db = $obj;
        return $this;
    }
    protected function getDb()
    {
        return $this->db;
    }

    public function redirect(String $configName, ?String $state)
    {
        $config = $this->getOAuth2Config();
        if(!isset($config[$configName]) && isset($config['default']) && is_string($config['default']) && isset($config[$config['default']])) {
            $configName = $config['default'];
        }
        if(!isset($config[$configName])) {
            throw new MethodNotFound();
        }
        $config = $config[$configName];

        $codeChallenge = CodeVerifier::getCodeVerifier();
        $session = new Container('oauth');
        $session['code_challenge'] = $codeChallenge['code'];
        $session['configName'] = $configName;

        $data = [
            'response_type' => 'code',
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'scope' => $config['scope'],
            'state' => $state,
            'code_challenge' => $codeChallenge['publicHash'],
            'code_challenge_method' => $codeChallenge['algo'],
        ];
        return $config['url-authorize'].'?'.http_build_query($data);
    }

    protected function validatedConfig($config)
    {
        $fields = array_flip(['url-authorize', 'url-token', 'redirect_uri', 'client_id', 'client_secret', 'scope']);
        foreach($config as $method => $values) {
            $missing = array_intersect_key($fields, $values);
            if(count($missing)) {
                throw new MissingMandatoryValue("OAuth method '{$method}' is missing mandatory '".implode("', '", $missing)."'");
            }
        }
        return true;
    }

    /**
    * Exchange the Authorization Code for the JWT
    *
    * @param String $authCode, the authorization code received by the client after granting access
    * @param String $configName, the name of the configuration used
    */
    public function getToken(String $authCode, String $configName, String $code_challenge): ?String
    {
        $session = new Container('oauth');
        $code_challenge = $session['code_challenge'];
        $configName = $session['configName'];

        $config = $this->getOAuth2Config();
        if(!isset($config[$configName])) {
            throw new MethodNotFound();
        }
        $config = $config[$configName];

        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $config['url-token']);
        $postdata = [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'redirect_uri' => $config['redirect_uri'],
            'code_verifier' => $code_challenge,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
        ];
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

        // grab URL and pass it to the browser
        $rawReturn = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // close cURL resource, and free up system resources
        curl_close($ch);

        if($httpcode != 200) {
            return null;
        }

        $json = json_decode($rawReturn, true);

        if(json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $db = $this->getDb();
        $prepared = $db->prepare("INSERT INTO oauth_clientside_tokens
            SET refreshToken = :refreshToken,
                token = :token,
                method = :method
        ");

        $prepared->execute([
            'refreshToken' => $json['refresh_token'],
            'token' => $json['access_token'],
            'method' => $configName,
        ]);

        return $json['access_token'];
    }
}
