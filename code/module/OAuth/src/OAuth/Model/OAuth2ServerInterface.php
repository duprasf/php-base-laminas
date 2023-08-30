<?php
namespace OAuth\Model;

interface OAuth2ServerInterface
{
    public function getAuthorizationCode(int $clientId, String $user_id, String $redirect_uri, array $scope, String $code_challenge, String $code_challenge_method, array $payload);
    public function getToken(String $code, String $redirect_uri, String $code_verifier, String $client_id, String $client_secret);
    public function verifyCodeVerifier(String $algoname='S256');
    public function verifyClient(String $client_id, String $redirectUri, array $scope, String $lang);
}
